<?php

namespace Tests\Feature;

use App\Models\Perkara;
use App\Models\PerkaraReminder;
use App\Models\PerkaraTimeline;
use App\Models\User;
use App\Services\Fcm\FcmSenderService;
use App\Services\Notification\NotificationService;
use App\Services\PerkaraWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PerkaraActionApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $jaksaAssigned;
    protected User $jaksaUnassigned;
    protected User $pidum;
    protected Perkara $perkara;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jaksaAssigned = User::factory()->create([
            'name' => 'Jaksa Peneliti, S.H.',
            'email' => 'jaksa_assigned@kejaksaan.go.id',
            'role' => 'jaksa',
        ]);

        $this->jaksaUnassigned = User::factory()->create([
            'name' => 'Jaksa Lain, S.H.',
            'email' => 'jaksa_other@kejaksaan.go.id',
            'role' => 'jaksa',
        ]);

        $this->pidum = User::factory()->create([
            'name' => 'Kasi Pidum',
            'email' => 'kasipidum@kejaksaan.go.id',
            'role' => 'pidum',
        ]);

        $this->perkara = Perkara::create([
            'nomor_perkara' => 'BP/001/X/2026/RESKRIM',
            'penyidik' => 'IPTU Penyidik Polri (Polres)',
            'jaksa' => $this->jaksaAssigned->name,
            'tanggal_spdp' => now()->subDays(5)->toDateString(),
            'tahap_saat_ini' => PerkaraWorkflowService::TAHAP_KOORDINASI,
            'status_saat_ini' => 'Menunggu Koordinasi',
        ]);

        // Attach jaksa ke pivot perkara_jaksa
        $this->perkara->jaksas()->attach($this->jaksaAssigned->id);
    }

    /**
     * 1. Tanpa autentikasi mendapat 401.
     */
    public function test_unauthenticated_request_returns_401(): void
    {
        $responseAvailable = $this->getJson("/api/perkara/{$this->perkara->id}/available-actions");
        $responseAvailable->assertStatus(401);

        $responseProcess = $this->postJson("/api/perkara/{$this->perkara->id}/actions", [
            'action_id' => 'koordinasi_selesai',
        ]);
        $responseProcess->assertStatus(401);
    }

    /**
     * 2. Registrasi dengan role 'penyidik' ditolak karena role penyidik telah dihapus dari sistem.
     */
    public function test_registration_rejects_deleted_penyidik_role(): void
    {
        $response = $this->post('/register', [
            'name' => 'Calon Penyidik',
            'email' => 'calon_penyidik@polri.go.id',
            'role' => 'penyidik',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertSessionHasErrors(['role']);
        $this->assertDatabaseMissing('users', [
            'email' => 'calon_penyidik@polri.go.id',
        ]);
    }

    /**
     * 3. Jaksa yang tidak ditugaskan ditolak.
     */
    public function test_unassigned_jaksa_is_forbidden(): void
    {
        Sanctum::actingAs($this->jaksaUnassigned);

        // GET available actions -> 403 Forbidden
        $responseAvailable = $this->getJson("/api/perkara/{$this->perkara->id}/available-actions");
        $responseAvailable->assertStatus(403)
            ->assertJson([
                'success' => false,
                'can_execute' => false,
            ]);

        // POST process action -> 403 Forbidden
        $responseProcess = $this->postJson("/api/perkara/{$this->perkara->id}/actions", [
            'action_id' => 'koordinasi_selesai',
        ]);
        $responseProcess->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * 4. Jaksa yang ditugaskan dapat mengambil daftar aksi dan menjalankan aksi yang valid.
     */
    public function test_assigned_jaksa_can_get_actions_and_execute_valid_action(): void
    {
        Sanctum::actingAs($this->jaksaAssigned);

        // GET available actions
        $responseAvailable = $this->getJson("/api/perkara/{$this->perkara->id}/available-actions");
        $responseAvailable->assertStatus(200)
            ->assertJson([
                'success' => true,
                'can_execute' => true,
            ]);

        $actions = $responseAvailable->json('data');
        $this->assertNotEmpty($actions);
        $this->assertEquals('koordinasi_selesai', $actions[0]['id']);

        // POST process action
        $responseProcess = $this->postJson("/api/perkara/{$this->perkara->id}/actions", [
            'action_id' => 'koordinasi_selesai',
            'tanggal' => now()->toDateString(),
            'catatan' => 'Koordinasi awal berkas SPDP selesai dengan penyidik.',
        ]);

        $responseProcess->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Aksi perkara berhasil diproses.',
                'data' => [
                    'id' => $this->perkara->id,
                    'tahap_saat_ini' => PerkaraWorkflowService::TAHAP_MONITORING,
                    'status_saat_ini' => 'Menunggu Berkas Tahap I',
                ],
            ]);

        $this->assertDatabaseHas('perkaras', [
            'id' => $this->perkara->id,
            'tahap_saat_ini' => PerkaraWorkflowService::TAHAP_MONITORING,
            'status_saat_ini' => 'Menunggu Berkas Tahap I',
        ]);

        $this->assertDatabaseHas('perkara_timelines', [
            'perkara_id' => $this->perkara->id,
            'user_id' => $this->jaksaAssigned->id,
            'tahap' => PerkaraWorkflowService::TAHAP_MONITORING,
            'status_kegiatan' => 'Koordinasi Selesai (Mulai Monitoring Penyidikan)',
        ]);
    }

    /**
     * 5. PIDUM mengikuti kebijakan akses proyek (dapat mengakses dan mengeksekusi semua perkara).
     */
    public function test_pidum_can_access_and_execute_any_perkara(): void
    {
        Sanctum::actingAs($this->pidum);

        // PIDUM tidak terdaftar di pivot perkara_jaksa, tapi punya hak supervisi
        $responseAvailable = $this->getJson("/api/perkara/{$this->perkara->id}/available-actions");
        $responseAvailable->assertStatus(200)
            ->assertJson([
                'success' => true,
                'can_execute' => true,
            ]);

        $responseProcess = $this->postJson("/api/perkara/{$this->perkara->id}/actions", [
            'action_id' => 'koordinasi_selesai',
            'catatan' => 'Disetujui oleh Kasi Pidum.',
        ]);

        $responseProcess->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);
    }

    /**
     * 6. Aksi tidak valid untuk tahap/status saat ini ditolak (409 Conflict).
     */
    public function test_invalid_action_for_current_stage_is_rejected(): void
    {
        Sanctum::actingAs($this->jaksaAssigned);

        // Perkara masih di Koordinasi Awal, mencoba mengeksekusi hasil_lengkap (P-24)
        $response = $this->postJson("/api/perkara/{$this->perkara->id}/actions", [
            'action_id' => 'hasil_lengkap',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'current_tahap' => PerkaraWorkflowService::TAHAP_KOORDINASI,
                'current_status' => 'Menunggu Koordinasi',
            ]);

        // Validasi wajib action_id (missing field) -> 422
        $responseMissing = $this->postJson("/api/perkara/{$this->perkara->id}/actions", []);
        $responseMissing->assertStatus(422)
            ->assertJsonValidationErrors(['action_id']);
    }

    /**
     * 7. P-24 hasil_lengkap menghasilkan P-21 dan reminder yang benar.
     */
    public function test_p24_hasil_lengkap_transitions_to_p21_with_correct_reminder(): void
    {
        Sanctum::actingAs($this->jaksaAssigned);

        // Siapkan perkara di Tahap P-24
        $this->perkara->update([
            'tahap_saat_ini' => PerkaraWorkflowService::TAHAP_P24,
            'status_saat_ini' => 'Menunggu Penelitian Berkas',
        ]);

        $response = $this->postJson("/api/perkara/{$this->perkara->id}/actions", [
            'action_id' => 'hasil_lengkap',
            'catatan' => 'Berkas perkara telah lengkap syarat materiil dan formil.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'tahap_saat_ini' => PerkaraWorkflowService::TAHAP_P21,
                    'status_saat_ini' => 'Menunggu Penyerahan Tahap II',
                ],
            ]);

        $this->assertDatabaseHas('perkaras', [
            'id' => $this->perkara->id,
            'tahap_saat_ini' => PerkaraWorkflowService::TAHAP_P21,
            'status_saat_ini' => 'Menunggu Penyerahan Tahap II',
        ]);

        // Verifikasi reminder SLA Penyerahan Tahap II (14 hari) dibuat dan aktif
        $this->assertDatabaseHas('perkara_reminders', [
            'perkara_id' => $this->perkara->id,
            'tahap' => PerkaraWorkflowService::TAHAP_P21,
            'jenis_reminder' => 'SLA Penyerahan Tahap II',
            'is_active' => true,
        ]);
    }

    /**
     * 8. P-24 hasil_belum_lengkap menghasilkan P-19 dan reminder yang benar.
     */
    public function test_p24_hasil_belum_lengkap_transitions_to_p19_with_correct_reminder(): void
    {
        Sanctum::actingAs($this->jaksaAssigned);

        // Siapkan perkara di Tahap P-24
        $this->perkara->update([
            'tahap_saat_ini' => PerkaraWorkflowService::TAHAP_P24,
            'status_saat_ini' => 'Menunggu Penelitian Berkas',
        ]);

        $response = $this->postJson("/api/perkara/{$this->perkara->id}/actions", [
            'action_id' => 'hasil_belum_lengkap',
            'catatan' => 'Berkas perkara belum lengkap. Terbit petunjuk P-18 dan P-19.',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'tahap_saat_ini' => PerkaraWorkflowService::TAHAP_P19,
                    'status_saat_ini' => 'Menunggu Pemenuhan Petunjuk',
                ],
            ]);

        $this->assertDatabaseHas('perkaras', [
            'id' => $this->perkara->id,
            'tahap_saat_ini' => PerkaraWorkflowService::TAHAP_P19,
            'status_saat_ini' => 'Menunggu Pemenuhan Petunjuk',
        ]);

        // Verifikasi reminder SLA Pemenuhan Petunjuk (14 hari) dibuat dan aktif
        $this->assertDatabaseHas('perkara_reminders', [
            'perkara_id' => $this->perkara->id,
            'tahap' => PerkaraWorkflowService::TAHAP_P19,
            'jenis_reminder' => 'SLA Pemenuhan Petunjuk',
            'is_active' => true,
        ]);
    }

    /**
     * 9. Stale state / double-submit tidak menimbulkan transisi ganda atau duplikasi timeline/reminder.
     */
    public function test_double_submit_and_stale_state_prevented(): void
    {
        Sanctum::actingAs($this->jaksaAssigned);

        $this->perkara->update([
            'tahap_saat_ini' => PerkaraWorkflowService::TAHAP_P24,
            'status_saat_ini' => 'Menunggu Penelitian Berkas',
        ]);

        // Eksekusi pertama berhasil
        $firstCall = $this->postJson("/api/perkara/{$this->perkara->id}/actions", [
            'action_id' => 'hasil_lengkap',
            'catatan' => 'Eksekusi pertama',
        ]);
        $firstCall->assertStatus(200);

        // Eksekusi kedua (simulasi double tap / stale state ketika status sudah berubah)
        $secondCall = $this->postJson("/api/perkara/{$this->perkara->id}/actions", [
            'action_id' => 'hasil_lengkap',
            'catatan' => 'Eksekusi kedua',
        ]);

        $secondCall->assertStatus(409)
            ->assertJson([
                'success' => false,
                'current_tahap' => PerkaraWorkflowService::TAHAP_P21,
                'current_status' => 'Menunggu Penyerahan Tahap II',
            ]);

        // Pastikan hanya ada tepat 1 timeline untuk aksi tersebut
        $timelineCount = PerkaraTimeline::where('perkara_id', $this->perkara->id)
            ->where('status_kegiatan', 'like', '%Berkas Dinyatakan Lengkap%')
            ->count();
        $this->assertEquals(1, $timelineCount);

        // Pastikan hanya ada tepat 1 reminder aktif untuk SLA Penyerahan Tahap II
        $reminderCount = PerkaraReminder::where('perkara_id', $this->perkara->id)
            ->where('tahap', PerkaraWorkflowService::TAHAP_P21)
            ->where('is_active', true)
            ->count();
        $this->assertEquals(1, $reminderCount);
    }

    /**
     * 10. Kegagalan pengiriman FCM tidak merusak konsistensi workflow.
     */
    public function test_fcm_failure_does_not_abort_workflow_transaction(): void
    {
        Sanctum::actingAs($this->jaksaAssigned);

        // Mock FcmSenderService agar melempar exception koneksi
        $mockFcm = $this->mock(FcmSenderService::class);
        $mockFcm->shouldReceive('sendMulticast')
            ->andThrow(new \RuntimeException('FCM Service Connection Timeout'));

        // Bind mock ke NotificationService
        $this->app->instance(NotificationService::class, new NotificationService($mockFcm));

        $response = $this->postJson("/api/perkara/{$this->perkara->id}/actions", [
            'action_id' => 'koordinasi_selesai',
            'catatan' => 'Pengujian resiliensi FCM gagal',
        ]);

        // Request tetap berhasil 200 OK karena exception FCM di-catch tanpa membatalkan transaksi DB
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Aksi perkara berhasil diproses.',
            ]);

        // Verifikasi database tetap terupdate secara konsisten
        $this->assertDatabaseHas('perkaras', [
            'id' => $this->perkara->id,
            'tahap_saat_ini' => PerkaraWorkflowService::TAHAP_MONITORING,
            'status_saat_ini' => 'Menunggu Berkas Tahap I',
        ]);

        $this->assertDatabaseHas('perkara_timelines', [
            'perkara_id' => $this->perkara->id,
            'status_kegiatan' => 'Koordinasi Selesai (Mulai Monitoring Penyidikan)',
        ]);
    }

    /**
     * Perkara tidak ditemukan mengembalikan 404.
     */
    /**
     * 12. Tahap Monitoring Penyidikan hanya menyediakan SATU tombol aksi manual: Konfirmasi Penerimaan Berkas (terima_berkas).
     * Tidak ada button manual catat_p17 maupun catat_p17_2 / catat_form3 karena penerbitan surat monitoring berjalan otomatis oleh SLA Engine.
     * Khusus pada Tahap P-24 tetap menyediakan 2 tombol perkondisian (P-21 Lengkap & P-19 Belum Lengkap).
     */
    public function test_monitoring_penyidikan_only_provides_single_terima_berkas_action(): void
    {
        Sanctum::actingAs($this->jaksaAssigned);

        // Kasus 1: Status Menunggu Berkas Tahap I
        $this->perkara->update([
            'tahap_saat_ini' => PerkaraWorkflowService::TAHAP_MONITORING,
            'status_saat_ini' => 'Menunggu Berkas Tahap I',
        ]);

        $res1 = $this->getJson("/api/perkara/{$this->perkara->id}/available-actions");
        $res1->assertStatus(200);
        $actions1 = $res1->json('data');
        $this->assertCount(1, $actions1);
        $this->assertEquals('terima_berkas', $actions1[0]['id']);
        $this->assertEquals('Konfirmasi Penerimaan Berkas', $actions1[0]['label']);

        // Kasus 2: Status P-17 (setelah auto-transition SLA 30 hari pertama)
        $this->perkara->update([
            'status_saat_ini' => 'P-17',
        ]);

        $res2 = $this->getJson("/api/perkara/{$this->perkara->id}/available-actions");
        $res2->assertStatus(200);
        $actions2 = $res2->json('data');
        $this->assertCount(1, $actions2);
        $this->assertEquals('terima_berkas', $actions2[0]['id']);

        // Kasus 3: Status P-17 Kedua / FORM-2 (setelah auto-transition SLA 30 hari kedua)
        $this->perkara->update([
            'status_saat_ini' => 'P-17 Kedua / FORM-2',
        ]);

        $res3 = $this->getJson("/api/perkara/{$this->perkara->id}/available-actions");
        $res3->assertStatus(200);
        $actions3 = $res3->json('data');
        $this->assertCount(1, $actions3);
        $this->assertEquals('terima_berkas', $actions3[0]['id']);

        // Manual eksekusi catat_p17 ditolak (409 Conflict) karena bukan aksi manual yang diizinkan
        $resReject = $this->postJson("/api/perkara/{$this->perkara->id}/actions", [
            'action_id' => 'catat_p17',
        ]);
        $resReject->assertStatus(409);

        // Eksekusi terima_berkas berhasil dan transisi ke Tahap P-24
        $resExecute = $this->postJson("/api/perkara/{$this->perkara->id}/actions", [
            'action_id' => 'terima_berkas',
            'catatan' => 'Berkas perkara Tahap I diterima dari penyidik.',
        ]);
        $resExecute->assertStatus(200);

        $this->assertDatabaseHas('perkaras', [
            'id' => $this->perkara->id,
            'tahap_saat_ini' => PerkaraWorkflowService::TAHAP_P24,
            'status_saat_ini' => 'Menunggu Penelitian Berkas',
        ]);

        // Kasus 4: Khusus pada tahap P-24 tetap tersedia 2 tombol opsi perkondisian (P-21 vs P-19)
        $resP24 = $this->getJson("/api/perkara/{$this->perkara->id}/available-actions");
        $resP24->assertStatus(200);
        $actionsP24 = collect($resP24->json('data'))->pluck('id')->toArray();
        $this->assertCount(2, $actionsP24);
        $this->assertContains('hasil_lengkap', $actionsP24);
        $this->assertContains('hasil_belum_lengkap', $actionsP24);
    }

    public function test_non_existent_perkara_returns_404(): void
    {
        Sanctum::actingAs($this->jaksaAssigned);

        $responseAvailable = $this->getJson('/api/perkara/99999/available-actions');
        $responseAvailable->assertStatus(404)
            ->assertJson(['success' => false]);

        $responseProcess = $this->postJson('/api/perkara/99999/actions', [
            'action_id' => 'koordinasi_selesai',
        ]);
        $responseProcess->assertStatus(404)
            ->assertJson(['success' => false]);
    }
}
