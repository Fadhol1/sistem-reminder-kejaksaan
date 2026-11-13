<?php

namespace Tests\Feature;

use App\Models\Berita;
use App\Models\User;
use App\Models\UserDeviceToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_password_reset_revokes_tokens_device_tokens_and_sessions(): void
    {
        $admin = User::factory()->create(['role' => 'pidum']);
        $target = User::factory()->create([
            'role' => 'jaksa',
            'password' => Hash::make('OldPass123!'),
        ]);

        $target->createToken('android-app-token');
        UserDeviceToken::create([
            'user_id' => $target->id,
            'device_id' => 'device-1',
            'device_type' => 'android',
            'fcm_token' => 'fake-fcm-token-123456',
            'fcm_token_hash' => UserDeviceToken::hashToken('fake-fcm-token-123456'),
            'is_active' => true,
        ]);

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->insert([
                'id' => 'test-session-target',
                'user_id' => $target->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'TestAgent',
                'payload' => base64_encode('test'),
                'last_activity' => time(),
            ]);
        }

        $response = $this->actingAs($admin)->put(route('users.update', $target->id), [
            'name' => $target->name,
            'email' => $target->email,
            'role' => 'jaksa',
            'password' => 'NewPass123!',
            'password_confirmation' => 'NewPass123!',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertSame(0, $target->tokens()->count());
        $this->assertDatabaseHas('user_device_tokens', [
            'user_id' => $target->id,
            'is_active' => false,
        ]);

        if (config('session.driver') === 'database') {
            $this->assertDatabaseMissing(config('session.table', 'sessions'), [
                'id' => 'test-session-target',
            ]);
        }
    }

    public function test_user_profile_password_change_revokes_sanctum_and_device_tokens(): void
    {
        $user = User::factory()->create([
            'role' => 'jaksa',
            'password' => Hash::make('OldPass123!'),
        ]);

        $user->createToken('android-app-token');
        UserDeviceToken::create([
            'user_id' => $user->id,
            'device_id' => 'device-2',
            'device_type' => 'android',
            'fcm_token' => 'fake-fcm-token-abcdef',
            'fcm_token_hash' => UserDeviceToken::hashToken('fake-fcm-token-abcdef'),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'current_password' => 'OldPass123!',
            'password' => 'NewPass456!',
            'password_confirmation' => 'NewPass456!',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $this->assertSame(0, $user->tokens()->count());
        $this->assertDatabaseHas('user_device_tokens', [
            'user_id' => $user->id,
            'is_active' => false,
        ]);
    }

    public function test_jaksa_cannot_view_draft_berita_but_pidum_can(): void
    {
        $pidum = User::factory()->create(['role' => 'pidum']);
        $jaksa = User::factory()->create(['role' => 'jaksa']);

        $draft = Berita::create([
            'judul' => 'Draf Internal Rahasia',
            'tanggal' => now()->toDateString(),
            'is_published' => false,
            'user_id' => $pidum->id,
        ]);

        $this->actingAs($jaksa)
            ->get(route('berita.show', $draft->id))
            ->assertStatus(403);

        $this->actingAs($pidum)
            ->get(route('berita.show', $draft->id))
            ->assertStatus(200);
    }

    public function test_api_pagination_per_page_is_capped_at_50(): void
    {
        $pidum = User::factory()->create(['role' => 'pidum']);
        Sanctum::actingAs($pidum);

        foreach (['/api/perkara', '/api/timelines', '/api/reminders', '/api/reminders/logs', '/api/notifications'] as $endpoint) {
            $response = $this->getJson($endpoint . '?per_page=999999');
            $response->assertStatus(200);
            $this->assertSame(50, $response->json('data.per_page'), "Pagination cap failed on {$endpoint}");
        }
    }

    public function test_security_headers_including_csp_are_present(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200)
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        $this->assertNotEmpty($response->headers->get('Content-Security-Policy'));
    }

    public function test_api_password_change_and_profile_update_revokes_other_tokens_and_sessions(): void
    {
        $user = User::factory()->create([
            'role' => 'jaksa',
            'password' => Hash::make('OldPass123!'),
        ]);

        $currentToken = $user->createToken('active-phone-token');
        $otherToken = $user->createToken('stolen-laptop-token');

        UserDeviceToken::create([
            'user_id' => $user->id,
            'device_id' => 'device-fcm-1',
            'device_type' => 'android',
            'fcm_token' => 'fcm-token-12345678',
            'fcm_token_hash' => UserDeviceToken::hashToken('fcm-token-12345678'),
            'is_active' => true,
        ]);

        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))->insert([
                'id' => 'test-api-session',
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'TestBrowser',
                'payload' => base64_encode('test'),
                'last_activity' => time(),
            ]);
        }

        // Test POST /api/password
        $response = $this->withToken($currentToken->plainTextToken)->postJson('/api/password', [
            'current_password' => 'OldPass123!',
            'password' => 'NewPass789!',
            'password_confirmation' => 'NewPass789!',
        ]);

        $response->assertStatus(200);
        $this->assertTrue(Hash::check('NewPass789!', $user->fresh()->password));

        // Token yang digunakan saat ini tetap aktif, token lain dicabut
        $this->assertTrue($user->tokens()->where('id', $currentToken->accessToken->id)->exists());
        $this->assertFalse($user->tokens()->where('id', $otherToken->accessToken->id)->exists());

        // Token perangkat lama dinonaktifkan
        $this->assertDatabaseHas('user_device_tokens', [
            'user_id' => $user->id,
            'is_active' => false,
        ]);

        // Sesi database dihapus
        if (config('session.driver') === 'database') {
            $this->assertDatabaseMissing(config('session.table', 'sessions'), [
                'id' => 'test-api-session',
            ]);
        }
    }

    public function test_api_password_change_is_rate_limited_on_failed_current_password(): void
    {
        $user = User::factory()->create([
            'role' => 'jaksa',
            'password' => Hash::make('CorrectPass123!'),
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        for ($i = 0; $i < 5; $i++) {
            $res = $this->withToken($token)->postJson('/api/password', [
                'current_password' => 'WrongPass!',
                'password' => 'NewValidPass123!',
                'password_confirmation' => 'NewValidPass123!',
            ]);
            $res->assertStatus(422);
        }

        // Percobaan ke-6 harus ditolak 429 Too Many Requests
        $res = $this->withToken($token)->postJson('/api/password', [
            'current_password' => 'WrongPass!',
            'password' => 'NewValidPass123!',
            'password_confirmation' => 'NewValidPass123!',
        ]);

        $res->assertStatus(429);
    }

    public function test_api_store_perkara_syncs_jaksa_to_pivot(): void
    {
        $pidum = User::factory()->create(['role' => 'pidum']);
        $jaksa = User::factory()->create(['role' => 'jaksa', 'name' => 'Jaksa Budi Utomo, S.H.']);

        Sanctum::actingAs($pidum);

        $payload = [
            'nomor_perkara' => 'BP/TEST/2026/001',
            'nama_tersangka' => 'Tersangka Satu',
            'penyidik' => 'Penyidik Satu',
            'jaksa' => 'Jaksa Budi Utomo, S.H.',
            'tanggal_spdp' => '2026-11-01',
            'tahap_saat_ini' => 'Koordinasi Awal',
            'status_saat_ini' => 'Menunggu Koordinasi',
        ];

        $response = $this->postJson('/api/perkara', $payload);
        $response->assertStatus(201);

        $perkaraId = $response->json('data.id');

        // Pastikan pivot perkara_jaksa terisi
        $this->assertDatabaseHas('perkara_jaksa', [
            'perkara_id' => $perkaraId,
            'jaksa_id' => $jaksa->id,
        ]);

        // Verifikasi bahwa Jaksa terkait bisa melihat perkaranya di GET /api/perkara
        Sanctum::actingAs($jaksa);
        $listResponse = $this->getJson('/api/perkara');
        $listResponse->assertStatus(200);
        $this->assertSame(1, $listResponse->json('data.total'));
    }

    public function test_perkara_destroy_cleans_up_all_reminders_and_penyidiks(): void
    {
        $pidum = User::factory()->create(['role' => 'pidum']);
        $perkara = \App\Models\Perkara::create([
            'nomor_perkara' => 'BP/CLEAN/2026/001',
            'penyidik' => 'Penyidik Lama',
            'jaksa' => 'Jaksa Lama',
            'tanggal_spdp' => '2026-11-01',
            'tahap_saat_ini' => 'Koordinasi Awal',
            'status_saat_ini' => 'Menunggu Koordinasi',
        ]);

        $perkara->penyidiks()->create(['nama' => 'Penyidik Lama']);
        $perkara->tersangkas()->create(['nama' => 'Tersangka Lama']);
        $activeRem = $perkara->reminders()->create([
            'jenis_reminder' => 'Reminder Aktif',
            'tanggal_mulai' => '2026-11-01',
            'deadline' => '2026-11-04',
            'is_active' => true,
        ]);
        $inactiveRem = $perkara->reminders()->create([
            'jenis_reminder' => 'Reminder Selesai',
            'tanggal_mulai' => '2026-11-01',
            'deadline' => '2026-11-04',
            'is_active' => false,
        ]);

        $response = $this->actingAs($pidum)->delete(route('perkara.destroy', $perkara->id));
        $response->assertRedirect(route('perkara.index'));

        $this->assertDatabaseMissing('perkaras', ['id' => $perkara->id]);
        $this->assertDatabaseMissing('penyidiks', ['perkara_id' => $perkara->id]);
        $this->assertDatabaseMissing('tersangkas', ['perkara_id' => $perkara->id]);
        $this->assertDatabaseMissing('perkara_reminders', ['id' => $activeRem->id]);
        $this->assertDatabaseMissing('perkara_reminders', ['id' => $inactiveRem->id]);
    }
}

