<?php

namespace Tests\Feature;

use App\Models\Perkara;
use App\Models\PerkaraReminder;
use App\Models\PushNotificationLog;
use App\Models\User;
use App\Models\UserDeviceToken;
use App\Services\Fcm\FcmSenderService;
use App\Services\Notification\NotificationService;
use App\Services\SLAEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FcmPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $jaksa;
    protected User $pidum;
    protected Perkara $perkara;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jaksa = User::factory()->create([
            'name' => 'Jaksa Utama, S.H.',
            'email' => 'jaksa@kejaksaan.go.id',
            'role' => 'jaksa',
        ]);

        $this->pidum = User::factory()->create([
            'name' => 'Admin PIDUM',
            'email' => 'pidum@kejaksaan.go.id',
            'role' => 'pidum',
        ]);

        $this->perkara = Perkara::create([
            'nomor_perkara' => 'BP/FCM/TEST/2026',
            'jaksa' => $this->jaksa->name,
            'penyidik' => 'IPTU Penyidik Polri',
            'tahap_saat_ini' => 'Monitoring Penyidikan',
            'status_saat_ini' => 'Menunggu Berkas Tahap I',
            'tanggal_spdp' => now()->subDays(10)->toDateString(),
        ]);

        // Attach jaksa via pivot perkara_jaksa
        $this->perkara->jaksas()->attach($this->jaksa->id);
    }

    /**
     * 1. Test Endpoint Registrasi Token Perangkat (Device Token Registration)
     */
    public function test_register_device_token_successfully_attaches_to_authenticated_user(): void
    {
        Sanctum::actingAs($this->jaksa);

        $payload = [
            'fcm_token' => 'fcm_fake_token_alpha_1234567890_test',
            'device_id' => 'device_android_xyz',
            'device_type' => 'android',
        ];

        $response = $this->postJson('/api/device-token', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'device_id' => 'device_android_xyz',
                    'device_type' => 'android',
                    'is_active' => true,
                ],
            ]);

        $this->assertDatabaseHas('user_device_tokens', [
            'user_id' => $this->jaksa->id,
            'device_id' => 'device_android_xyz',
            'is_active' => true,
        ]);
    }

    /**
     * 2. Test Token Rotation: saat user lain login di perangkat/token yang sama
     */
    public function test_device_token_rotation_when_new_user_logs_in(): void
    {
        // Token pertama kali terdaftar oleh Jaksa
        $tokenStr = 'fcm_shared_tablet_token_9876543210';
        UserDeviceToken::create([
            'user_id' => $this->jaksa->id,
            'device_id' => 'tablet_office_01',
            'device_type' => 'android',
            'fcm_token' => $tokenStr,
            'fcm_token_hash' => UserDeviceToken::hashToken($tokenStr),
            'is_active' => true,
        ]);

        // Sekarang User lain (Admin PIDUM) login di perangkat yang sama
        Sanctum::actingAs($this->pidum);

        $response = $this->postJson('/api/device-token', [
            'fcm_token' => $tokenStr,
            'device_id' => 'tablet_office_01',
        ]);

        $response->assertStatus(200);

        // Token sekarang harus menjadi milik PIDUM dan tetap aktif
        $this->assertDatabaseHas('user_device_tokens', [
            'user_id' => $this->pidum->id,
            'fcm_token_hash' => UserDeviceToken::hashToken($tokenStr),
            'is_active' => true,
        ]);

        // Tidak boleh ada token aktif tersisa untuk Jaksa dengan token tersebut
        $this->assertDatabaseMissing('user_device_tokens', [
            'user_id' => $this->jaksa->id,
            'fcm_token_hash' => UserDeviceToken::hashToken($tokenStr),
        ]);
    }

    /**
     * 3. Test Pencabutan Token Perangkat (Token Revocation on Logout)
     */
    public function test_revoke_device_token_successfully_deactivates(): void
    {
        Sanctum::actingAs($this->jaksa);

        $tokenStr = 'fcm_token_to_be_revoked_11223344';
        UserDeviceToken::create([
            'user_id' => $this->jaksa->id,
            'device_id' => 'hp_jaksa_01',
            'fcm_token' => $tokenStr,
            'fcm_token_hash' => UserDeviceToken::hashToken($tokenStr),
            'is_active' => true,
        ]);

        $response = $this->deleteJson('/api/device-token', [
            'fcm_token' => $tokenStr,
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('user_device_tokens', [
            'user_id' => $this->jaksa->id,
            'fcm_token_hash' => UserDeviceToken::hashToken($tokenStr),
            'is_active' => false,
        ]);
    }

    /**
     * 4. Test Pemilihan Penerima (Recipient Resolution)
     */
    public function test_notification_recipient_resolution(): void
    {
        $service = new NotificationService();

        // Reminder H-3: Hanya Jaksa terkait yang menerima
        $recipientsH3 = $service->resolveRecipients($this->perkara, NotificationService::EVENT_REMINDER_H3);
        $this->assertTrue($recipientsH3->contains('id', $this->jaksa->id));
        $this->assertFalse($recipientsH3->contains('id', $this->pidum->id));

        // Auto P-17: Jaksa terkait menerima surat
        $recipientsP17 = $service->resolveRecipients($this->perkara, NotificationService::EVENT_AUTO_P17);
        $this->assertTrue($recipientsP17->contains('id', $this->jaksa->id));

        // Auto P-20 / FORM-3 / FORM-7 (Arsip): Jaksa terkait dan Admin PIDUM menerima
        $recipientsP20 = $service->resolveRecipients($this->perkara, NotificationService::EVENT_AUTO_P20);
        $this->assertTrue($recipientsP20->contains('id', $this->jaksa->id));
        $this->assertTrue($recipientsP20->contains('id', $this->pidum->id));
    }

    /**
     * 5. Test Pengiriman FCM Berhasil dan Idempotensi / Anti-Deduplikasi
     */
    public function test_push_notification_dispatch_and_idempotency_deduplication(): void
    {
        // Daftarkan token aktif untuk Jaksa
        $tokenStr = 'fcm_valid_jaksa_token_123456';
        UserDeviceToken::create([
            'user_id' => $this->jaksa->id,
            'device_id' => 'jaksa_phone',
            'fcm_token' => $tokenStr,
            'fcm_token_hash' => UserDeviceToken::hashToken($tokenStr),
            'is_active' => true,
        ]);

        // Buat mock FcmSenderService yang mensimulasikan respons sukses dari FCM
        $mockFcm = $this->createMock(FcmSenderService::class);
        $mockFcm->expects($this->once()) // Harus dipanggil tepat 1 KALI, panggilan kedua harus di-skip oleh idempotency
            ->method('sendToToken')
            ->willReturn([
                'success' => true,
                'message_id' => 'projects/reminderskejaksaan/messages/mock_msg_001',
                'error' => null,
                'is_invalid_token' => false,
            ]);

        $service = new NotificationService($mockFcm);

        $reminder = PerkaraReminder::create([
            'perkara_id' => $this->perkara->id,
            'tahap' => 'Monitoring Penyidikan',
            'jenis_reminder' => 'Batas Waktu Penyerahan Tahap I (30 Hari)',
            'tanggal_mulai' => now()->subDays(27),
            'deadline' => now()->addDays(3),
            'status_urgensi' => 'H-3',
            'is_active' => true,
        ]);

        // Eksekusi Panggilan Pertama
        $results1 = $service->notifyReminderUrgency($reminder, 'H-3');
        $this->assertCount(1, $results1);
        $this->assertEquals(PushNotificationLog::STATUS_SENT, $results1[0]['status']);

        // Verifikasi log tersimpan di database
        $this->assertDatabaseHas('push_notification_logs', [
            'user_id' => $this->jaksa->id,
            'perkara_id' => $this->perkara->id,
            'status' => PushNotificationLog::STATUS_SENT,
            'fcm_message_id' => 'projects/reminderskejaksaan/messages/mock_msg_001',
        ]);

        // Eksekusi Panggilan Kedua (simulasi SLA engine berjalan lagi dalam waktu berdekatan)
        $results2 = $service->notifyReminderUrgency($reminder, 'H-3');
        $this->assertCount(1, $results2);
        $this->assertEquals('SKIPPED_DUPLICATE', $results2[0]['status']);

        // Jumlah entri di database harus tetap 1 (tidak terduplikasi)
        $this->assertEquals(1, PushNotificationLog::where('user_id', $this->jaksa->id)->count());
    }

    /**
     * 6. Test Penanganan Token FCM Invalid / Unregistered
     */
    public function test_handling_invalid_fcm_token_deactivates_device(): void
    {
        $invalidTokenStr = 'fcm_invalid_expired_token_00000';
        $tokenRecord = UserDeviceToken::create([
            'user_id' => $this->jaksa->id,
            'device_id' => 'jaksa_old_phone',
            'fcm_token' => $invalidTokenStr,
            'fcm_token_hash' => UserDeviceToken::hashToken($invalidTokenStr),
            'is_active' => true,
        ]);

        // Mock FCM mengembalikan token unregistered / invalid
        $mockFcm = $this->createMock(FcmSenderService::class);
        $mockFcm->method('sendToToken')
            ->willReturn([
                'success' => false,
                'message_id' => null,
                'error' => 'Requested entity was not found (UNREGISTERED).',
                'is_invalid_token' => true,
            ]);

        $service = new NotificationService($mockFcm);

        $reminder = PerkaraReminder::create([
            'perkara_id' => $this->perkara->id,
            'tahap' => 'Monitoring Penyidikan',
            'jenis_reminder' => 'Penataan Berkas',
            'tanggal_mulai' => now()->subDays(30),
            'deadline' => now(),
            'status_urgensi' => 'Jatuh Tempo',
            'is_active' => true,
        ]);

        $results = $service->notifyReminderUrgency($reminder, 'Jatuh Tempo');
        $this->assertEquals(PushNotificationLog::STATUS_INVALID_TOKEN, $results[0]['status']);

        // Token perangkat harus otomatis dinonaktifkan
        $tokenRecord->refresh();
        $this->assertFalse($tokenRecord->is_active);
    }

    /**
     * 7. Test Minimal Payload (Tidak membocorkan data sensitif)
     */
    public function test_push_payload_contains_only_minimal_safe_identifiers(): void
    {
        $mockFcm = $this->createMock(FcmSenderService::class);
        $capturedData = null;

        $mockFcm->expects($this->once())
            ->method('sendToToken')
            ->willReturnCallback(function ($token, $title, $body, $data) use (&$capturedData) {
                $capturedData = $data;
                return [
                    'success' => true,
                    'message_id' => 'msg_test',
                    'error' => null,
                    'is_invalid_token' => false,
                ];
            });

        UserDeviceToken::create([
            'user_id' => $this->jaksa->id,
            'fcm_token' => 'fcm_tok_safe_test',
            'fcm_token_hash' => UserDeviceToken::hashToken('fcm_tok_safe_test'),
            'is_active' => true,
        ]);

        $service = new NotificationService($mockFcm);
        $service->notifyAutoTransition($this->perkara, 'catat_p17');

        $this->assertNotNull($capturedData);
        $this->assertArrayHasKey('perkara_id', $capturedData);
        $this->assertArrayHasKey('nomor_perkara', $capturedData);
        $this->assertArrayHasKey('tahap', $capturedData);
        $this->assertArrayHasKey('event_type', $capturedData);
        $this->assertEquals('OPEN_PERKARA_DETAIL', $capturedData['click_action']);

        // Pastikan tidak ada data sensitif terselubung
        $this->assertArrayNotHasKey('nik', $capturedData);
        $this->assertArrayNotHasKey('pasal', $capturedData);
        $this->assertArrayNotHasKey('saksi', $capturedData);
    }

    /**
     * 8. Test Endpoint Riwayat Notifikasi (/api/notifications)
     */
    public function test_get_notifications_history_api(): void
    {
        Sanctum::actingAs($this->jaksa);

        PushNotificationLog::create([
            'user_id' => $this->jaksa->id,
            'perkara_id' => $this->perkara->id,
            'event_type' => NotificationService::EVENT_REMINDER_H3,
            'idempotency_key' => 'key_history_test_01',
            'title' => 'Reminder H-3',
            'body' => 'Batas waktu segera tiba.',
            'status' => PushNotificationLog::STATUS_SENT,
            'sent_at' => now(),
        ]);

        $response = $this->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'user_id',
                            'event_type',
                            'title',
                            'body',
                            'status',
                        ],
                    ],
                ],
            ]);
    }

    /**
     * 9. Test Pemanggilan Langsung dari Alur SLA Engine (H-3 & Deduplikasi saat Engine Running)
     */
    public function test_sla_engine_integration_triggers_push_notifications(): void
    {
        // Daftarkan token aktif untuk Jaksa
        $tokenStr = 'fcm_jaksa_sla_engine_token_777';
        UserDeviceToken::create([
            'user_id' => $this->jaksa->id,
            'fcm_token' => $tokenStr,
            'fcm_token_hash' => UserDeviceToken::hashToken($tokenStr),
            'is_active' => true,
        ]);

        $mockFcm = $this->createMock(FcmSenderService::class);
        $mockFcm->expects($this->once()) // Meskipun executeEngine dijalankan 2 kali, push hanya dikirim 1 kali
            ->method('sendToToken')
            ->willReturn([
                'success' => true,
                'message_id' => 'projects/reminderskejaksaan/messages/sla_engine_msg_01',
                'error' => null,
                'is_invalid_token' => false,
            ]);

        // Bind instance mock ke container Laravel
        $this->app->instance(FcmSenderService::class, $mockFcm);

        // Buat reminder dengan deadline 3 hari lagi (masuk kriteria H-3)
        PerkaraReminder::create([
            'perkara_id' => $this->perkara->id,
            'tahap' => 'Monitoring Penyidikan',
            'jenis_reminder' => 'Batas Waktu Penyerahan Tahap I (30 Hari)',
            'tanggal_mulai' => now()->subDays(27),
            'deadline' => now()->addDays(3),
            'status_urgensi' => 'Aman', // Sebelum dievaluasi engine
            'is_active' => true,
        ]);

        $slaEngine = app(SLAEngineService::class);

        // Eksekusi SLA Engine putaran pertama
        $slaEngine->executeEngine();

        // Pastikan notifikasi tercatat dengan status SENT
        $this->assertDatabaseHas('push_notification_logs', [
            'user_id' => $this->jaksa->id,
            'perkara_id' => $this->perkara->id,
            'event_type' => NotificationService::EVENT_REMINDER_H3,
            'status' => PushNotificationLog::STATUS_SENT,
        ]);

        // Eksekusi SLA Engine putaran kedua (menyimulasikan cron/on-demand run kedua)
        $slaEngine->executeEngine();

        // Jumlah entri push log untuk event H-3 pada perkara ini harus tetap 1 (deduplikasi terjaga)
        $this->assertEquals(1, PushNotificationLog::where('user_id', $this->jaksa->id)
            ->where('event_type', NotificationService::EVENT_REMINDER_H3)
            ->count());
    }
}
