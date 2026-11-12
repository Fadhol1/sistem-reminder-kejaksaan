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
}

