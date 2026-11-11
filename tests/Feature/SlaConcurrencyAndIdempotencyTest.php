<?php

namespace Tests\Feature;

use App\Models\Perkara;
use App\Models\PerkaraReminder;
use App\Models\PerkaraTimeline;
use App\Models\PushNotificationLog;
use App\Models\User;
use App\Models\UserDeviceToken;
use App\Services\Fcm\FcmSenderService;
use App\Services\Notification\NotificationService;
use App\Services\PerkaraWorkflowService;
use App\Services\SLAEngineService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SlaConcurrencyAndIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $jaksa;
    protected User $pidum;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jaksa = User::factory()->create([
            'name' => 'Jaksa Peneliti, S.H.',
            'email' => 'jaksa.concurrency@kejaksaan.go.id',
            'role' => 'jaksa',
        ]);

        $this->pidum = User::factory()->create([
            'name' => 'Admin PIDUM',
            'email' => 'pidum.concurrency@kejaksaan.go.id',
            'role' => 'pidum',
        ]);
    }

    /**
     * 1. Test Mutex Lock Mencegah Overlapping pada SLA Engine
     */
    public function test_sla_engine_mutex_prevents_concurrent_overlapping_executions(): void
    {
        // Simulasikan proses lain sedang memegang lock mutex eksekusi mesin
        $lock = Cache::lock('sla_engine_running_mutex', 60);
        $this->assertTrue($lock->get());

        // Buat perkara SPDP > 30 hari
        $perkara = Perkara::create([
            'nomor_perkara' => 'BP/MUTEX/01',
            'jaksa' => $this->jaksa->name,
            'penyidik' => 'Penyidik Polres',
            'tahap_saat_ini' => PerkaraWorkflowService::TAHAP_MONITORING,
            'status_saat_ini' => 'Menunggu Berkas Tahap I',
            'tanggal_spdp' => now()->subDays(35)->toDateString(),
        ]);

        // Jalankan executeEngine saat lock masih aktif
        $service = new SLAEngineService();
        $service->executeEngine();

        // Verifikasi bahwa eksekusi di-skip sehingga status perkara tidak berubah saat lock aktif
        $perkara->refresh();
        $this->assertEquals(PerkaraWorkflowService::TAHAP_MONITORING, $perkara->tahap_saat_ini);
        $this->assertEquals('Menunggu Berkas Tahap I', $perkara->status_saat_ini);

        // Lepas lock
        $lock->release();

        // Jalankan kembali executeEngine saat lock sudah bebas
        $service->executeEngine();

        // Verifikasi transisi berhasil berjalan setelah lock dilepas
        $perkara->refresh();
        $this->assertEquals('P-17', $perkara->status_saat_ini);
    }

    /**
     * 2. Test Atomic Lock pada HTTP Throttling (runThrottled)
     */
    public function test_run_throttled_uses_atomic_distributed_lock(): void
    {
        // Kunci throttle secara manual selama 15 detik
        $lock = Cache::lock('sla_engine_last_throttled_run', 15);
        $this->assertTrue($lock->get());

        $perkara = Perkara::create([
            'nomor_perkara' => 'BP/THROTTLE/01',
            'jaksa' => $this->jaksa->name,
            'penyidik' => 'Penyidik Polres',
            'tahap_saat_ini' => PerkaraWorkflowService::TAHAP_MONITORING,
            'status_saat_ini' => 'Menunggu Berkas Tahap I',
            'tanggal_spdp' => now()->subDays(40)->toDateString(),
        ]);

        // Panggil runThrottled saat lock throttle masih aktif
        SLAEngineService::runThrottled(15);

        // Verifikasi bahwa runThrottled keluar tanpa mengeksekusi transisi
        $perkara->refresh();
        $this->assertEquals('Menunggu Berkas Tahap I', $perkara->status_saat_ini);

        $lock->release();
    }

    /**
     * 3. Test Proteksi Stale-Model Auto-Transition Abort (Lost Update Prevention)
     */
    public function test_stale_model_auto_transition_aborts_when_user_already_progressed_stage(): void
    {
        $perkara = Perkara::create([
            'nomor_perkara' => 'BP/STALE/01',
            'jaksa' => $this->jaksa->name,
            'penyidik' => 'Penyidik Polres',
            'tahap_saat_ini' => PerkaraWorkflowService::TAHAP_MONITORING,
            'status_saat_ini' => 'Menunggu Berkas Tahap I',
            'tanggal_spdp' => now()->subDays(35)->toDateString(),
        ]);

        // Simulasikan salinan model lama yang di-cache di memori background
        $stalePerkara = Perkara::find($perkara->id);

        // Jaksa secara manual mencatat penerimaan berkas (P-24)
        PerkaraWorkflowService::processAction(
            $perkara,
            'terima_berkas',
            ['tanggal' => now()->toDateString()],
            $this->jaksa->id,
            'USER'
        );

        $perkara->refresh();
        $this->assertEquals(PerkaraWorkflowService::TAHAP_P24, $perkara->tahap_saat_ini);
        $this->assertEquals('Menunggu Penelitian Berkas', $perkara->status_saat_ini);

        // Sekarang simulasikan thread mesin SLA mencoba mengeksekusi auto-transition catat_p17 dengan model lama
        $result = PerkaraWorkflowService::processAction(
            $stalePerkara,
            'catat_p17',
            [],
            null,
            'SYSTEM'
        );
        $this->assertFalse($result);

        // State database HARUS TETAP P-24 (tidak boleh tertimpa / mundur ke P-17)
        $perkara->refresh();
        $this->assertEquals(PerkaraWorkflowService::TAHAP_P24, $perkara->tahap_saat_ini);
        $this->assertEquals('Menunggu Penelitian Berkas', $perkara->status_saat_ini);
    }

    /**
     * 4. Test Pre-Dispatch Notification Locking & Retry pada Status FAILED
     */
    public function test_notification_retries_when_previous_status_was_failed(): void
    {
        $perkara = Perkara::create([
            'nomor_perkara' => 'BP/NOTIF/RETRY/01',
            'jaksa' => $this->jaksa->name,
            'penyidik' => 'Penyidik Polres',
            'tanggal_spdp' => now()->subDays(10)->toDateString(),
            'tahap_saat_ini' => PerkaraWorkflowService::TAHAP_P24,
            'status_saat_ini' => 'Menunggu Penelitian Berkas',
        ]);
        $perkara->jaksas()->attach($this->jaksa->id);

        $tokenStr = 'fcm_retry_token_999';
        UserDeviceToken::create([
            'user_id' => $this->jaksa->id,
            'device_id' => 'device_retry_1',
            'fcm_token' => $tokenStr,
            'fcm_token_hash' => UserDeviceToken::hashToken($tokenStr),
            'is_active' => true,
        ]);

        $reminder = PerkaraReminder::create([
            'perkara_id' => $perkara->id,
            'tahap' => PerkaraWorkflowService::TAHAP_P24,
            'jenis_reminder' => 'SLA Penelitian Berkas (P-24)',
            'tanggal_mulai' => now()->subDays(7),
            'deadline' => now(),
            'status_urgensi' => 'Jatuh Tempo',
            'is_active' => true,
        ]);

        $dateKey = now()->format('Y-m-d');
        $idempotencyKey = "rem_{$reminder->id}_Jatuh Tempo_{$dateKey}_u{$this->jaksa->id}";

        // Simulasikan ada log pengiriman yang FAILED sebelumnya (misal jaringan down)
        PushNotificationLog::create([
            'user_id' => $this->jaksa->id,
            'perkara_id' => $perkara->id,
            'perkara_reminder_id' => $reminder->id,
            'event_type' => NotificationService::EVENT_REMINDER_H0,
            'idempotency_key' => $idempotencyKey,
            'title' => 'Test Gagal',
            'body' => 'Gagal kirim awal',
            'status' => PushNotificationLog::STATUS_FAILED,
            'error_message' => 'Connection timeout to FCM',
        ]);

        // Buat mock FCM yang sekarang berhasil mengirim
        $mockFcm = $this->createMock(FcmSenderService::class);
        $mockFcm->expects($this->once())
            ->method('sendToToken')
            ->willReturn([
                'success' => true,
                'message_id' => 'projects/test/messages/msg_recovered_001',
                'error' => null,
                'is_invalid_token' => false,
            ]);

        $notificationService = new NotificationService($mockFcm);
        $results = $notificationService->notifyReminderUrgency($reminder, 'Jatuh Tempo');

        // Verifikasi pengiriman berhasil di-retry dan tidak di-skip
        $this->assertNotEmpty($results);
        $this->assertEquals(PushNotificationLog::STATUS_SENT, $results[0]['status']);

        // Verifikasi database log status berubah menjadi SENT
        $log = PushNotificationLog::where('idempotency_key', $idempotencyKey)->first();
        $this->assertNotNull($log);
        $this->assertEquals(PushNotificationLog::STATUS_SENT, $log->status);
        $this->assertEquals('projects/test/messages/msg_recovered_001', $log->fcm_message_id);
    }

    /**
     * 5. Test Notifikasi Dicoba Kembali Ketika Sebelumnya STATUS_NO_TOKEN dan User Baru Mendaftarkan Token
     */
    public function test_notification_retries_when_previous_status_was_no_token_and_user_now_has_active_token(): void
    {
        // Pastikan token jaksa belum terdaftar pada awal pengujian
        UserDeviceToken::where('user_id', $this->jaksa->id)->delete();

        $perkara = Perkara::create([
            'nomor_perkara' => 'BP/RETRY-NO-TOKEN/01',
            'jaksa' => $this->jaksa->name,
            'penyidik' => 'Penyidik Polres',
            'tahap_saat_ini' => PerkaraWorkflowService::TAHAP_MONITORING,
            'status_saat_ini' => 'Menunggu Berkas Tahap I',
            'tanggal_spdp' => now()->subDays(10)->toDateString(),
        ]);
        $perkara->jaksas()->attach($this->jaksa->id);

        $reminder = PerkaraReminder::create([
            'perkara_id' => $perkara->id,
            'tahap' => PerkaraWorkflowService::TAHAP_MONITORING,
            'jenis_reminder' => 'Batas Waktu Penyerahan Tahap I (30 Hari)',
            'tanggal_mulai' => now()->subDays(27),
            'deadline' => now()->addDays(3),
            'status_urgensi' => 'H-3',
            'is_active' => true,
        ]);

        $mockFcm = $this->createMock(FcmSenderService::class);
        $mockFcm->expects($this->once())
            ->method('sendToToken')
            ->willReturn([
                'success' => true,
                'message_id' => 'projects/test/messages/msg_token_arrived_001',
                'error' => null,
                'is_invalid_token' => false,
            ]);

        $service = new NotificationService($mockFcm);

        // Run pertama: Pengguna belum memiliki token sama sekali
        $results1 = $service->notifyReminderUrgency($reminder, 'H-3');
        $this->assertEquals(PushNotificationLog::STATUS_NO_TOKEN, $results1[0]['status']);

        // Verifikasi log tersimpan dengan status NO_TOKEN
        $dateKey = now()->format('Y-m-d');
        $idempotencyKey = "rem_{$reminder->id}_H-3_{$dateKey}_u{$this->jaksa->id}";
        $this->assertDatabaseHas('push_notification_logs', [
            'idempotency_key' => $idempotencyKey,
            'status' => PushNotificationLog::STATUS_NO_TOKEN,
        ]);

        // Pengguna baru saja login dan mendaftarkan token perangkat aktif di HP
        $newTokenStr = 'fcm_jaksa_newly_registered_token_999';
        UserDeviceToken::create([
            'user_id' => $this->jaksa->id,
            'device_id' => 'jaksa_smartphone_new',
            'device_type' => 'android',
            'fcm_token' => $newTokenStr,
            'fcm_token_hash' => UserDeviceToken::hashToken($newTokenStr),
            'is_active' => true,
        ]);

        // Run kedua: SLA Engine berjalan lagi, sekarang notifikasi harus langsung terkirim!
        $results2 = $service->notifyReminderUrgency($reminder, 'H-3');
        $this->assertEquals(PushNotificationLog::STATUS_SENT, $results2[0]['status']);

        // Log di database harus terupdate menjadi SENT
        $log = PushNotificationLog::where('idempotency_key', $idempotencyKey)->first();
        $this->assertNotNull($log);
        $this->assertEquals(PushNotificationLog::STATUS_SENT, $log->status);
        $this->assertEquals('projects/test/messages/msg_token_arrived_001', $log->fcm_message_id);
    }

    /**
     * 6. Test Undo Last Action Aman dengan Row Locking & Audit Trail
     */
    public function test_undo_last_action_with_row_locking_and_audit_trail(): void
    {
        $perkara = Perkara::create([
            'nomor_perkara' => 'BP/UNDO/01',
            'jaksa' => $this->jaksa->name,
            'penyidik' => 'Penyidik Polres',
            'tahap_saat_ini' => PerkaraWorkflowService::TAHAP_MONITORING,
            'status_saat_ini' => 'Menunggu Berkas Tahap I',
            'tanggal_spdp' => now()->subDays(5)->toDateString(),
        ]);

        // Jalankan aksi: Penerbitan P-17
        PerkaraWorkflowService::processAction(
            $perkara,
            'catat_p17',
            ['tanggal' => now()->toDateString(), 'catatan' => 'Surat P-17 dikirim'],
            $this->pidum->id,
            'USER'
        );

        $perkara->refresh();
        $this->assertEquals('P-17', $perkara->status_saat_ini);

        // Batalkan (undo) aksi tersebut
        $correction = PerkaraWorkflowService::undoLastAction(
            $perkara,
            'Salah pilih berkas perkara saat input P-17',
            $this->pidum->id
        );

        $this->assertNotNull($correction);
        $perkara->refresh();

        // Status perkara kembali ke Menunggu Berkas Tahap I
        $this->assertEquals(PerkaraWorkflowService::TAHAP_MONITORING, $perkara->tahap_saat_ini);
        $this->assertEquals('Menunggu Berkas Tahap I', $perkara->status_saat_ini);

        // Audit trail tercatat rapi
        $this->assertDatabaseHas('perkara_timelines', [
            'perkara_id' => $perkara->id,
            'status_kegiatan' => '[KOREKSI ADMINISTRASI] Pembatalan Aksi: Penerbitan P-17 (Permintaan Perkembangan Penyidikan)',
            'user_id' => $this->pidum->id,
            'actor_type' => 'USER',
        ]);
    }

    /**
     * 6. Test Eliminasi Phantom Notification: Notifikasi tidak boleh dikirim jika auto-transition abort
     */
    public function test_phantom_notification_is_never_sent_when_auto_transition_aborts_due_to_stale_state(): void
    {
        $perkara = Perkara::create([
            'nomor_perkara' => 'BP/PHANTOM/01',
            'jaksa' => $this->jaksa->name,
            'penyidik' => 'Penyidik Polres',
            'tahap_saat_ini' => PerkaraWorkflowService::TAHAP_MONITORING,
            'status_saat_ini' => 'Menunggu Berkas Tahap I',
            'tanggal_spdp' => now()->subDays(35)->toDateString(),
        ]);
        $perkara->jaksas()->attach($this->jaksa->id);

        $tokenStr = 'fcm_phantom_test_token_123';
        UserDeviceToken::create([
            'user_id' => $this->jaksa->id,
            'device_id' => 'device_phantom_1',
            'fcm_token' => $tokenStr,
            'fcm_token_hash' => UserDeviceToken::hashToken($tokenStr),
            'is_active' => true,
        ]);

        // Simulasikan stale model
        $stalePerkara = Perkara::find($perkara->id);

        // Jaksa secara manual memajukan perkara ke tahap P-24
        $manualResult = PerkaraWorkflowService::processAction(
            $perkara,
            'terima_berkas',
            ['tanggal' => now()->toDateString()],
            $this->jaksa->id,
            'USER'
        );
        $this->assertTrue($manualResult);

        // Mock FcmSenderService dan pastikan sendToToken TIDAK PERNAH dipanggil untuk P-17!
        $mockFcm = $this->createMock(FcmSenderService::class);
        $mockFcm->expects($this->never())->method('sendToToken');
        $this->app->instance(FcmSenderService::class, $mockFcm);

        // Jalankan auto-transition dengan stale model
        $transitionResult = PerkaraWorkflowService::processAction(
            $stalePerkara,
            'catat_p17',
            [],
            null,
            'SYSTEM'
        );

        // Verifikasi bahwa transisi mengembalikan false (abort)
        $this->assertFalse($transitionResult);

        // Verifikasi tidak ada PushNotificationLog untuk P-17 pada perkara ini
        $this->assertDatabaseMissing('push_notification_logs', [
            'perkara_id' => $perkara->id,
            'event_type' => NotificationService::EVENT_AUTO_P17,
        ]);
    }
}
