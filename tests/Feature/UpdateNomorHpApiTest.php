<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateNomorHpApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'name'     => 'Jaksa Pratama',
            'email'    => 'jaksa.pratama@kejaksaan.go.id',
            'role'     => 'jaksa',
            'nomor_hp' => '081200000000',
        ]);
        Sanctum::actingAs($this->user);
    }

    public function test_update_nomor_hp_via_dedicated_endpoint_put(): void
    {
        $response = $this->putJson('/api/user/nomor-hp', [
            'nomor_hp' => '081234567890',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Nomor HP berhasil diperbarui',
                'data'    => [
                    'nomor_hp' => '081234567890',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id'       => $this->user->id,
            'nomor_hp' => '081234567890',
        ]);
    }

    public function test_update_nomor_hp_via_dedicated_endpoint_post_with_alias_no_hp(): void
    {
        $response = $this->postJson('/api/profile/nomor-hp', [
            'no_hp' => '085711223344',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Nomor HP berhasil diperbarui',
                'data'    => [
                    'nomor_hp' => '085711223344',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id'       => $this->user->id,
            'nomor_hp' => '085711223344',
        ]);
    }

    public function test_update_nomor_hp_via_phone_endpoint(): void
    {
        $response = $this->postJson('/api/phone', [
            'phone' => '089988776655',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Nomor HP berhasil diperbarui',
                'data'    => [
                    'nomor_hp' => '089988776655',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id'       => $this->user->id,
            'nomor_hp' => '089988776655',
        ]);
    }

    public function test_update_nomor_hp_via_general_profile_update(): void
    {
        $response = $this->putJson('/api/profile', [
            'nomor_hp' => '082199998888',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Profil berhasil diperbarui',
                'data'    => [
                    'nomor_hp' => '082199998888',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id'       => $this->user->id,
            'nomor_hp' => '082199998888',
        ]);
    }
}
