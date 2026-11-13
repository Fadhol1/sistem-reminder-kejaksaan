<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UpdateFotoProfilApiTest extends TestCase
{
    use RefreshDatabase;

    private function createFakeImageFile(string $name = 'profile.png'): UploadedFile
    {
        $rawPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        return UploadedFile::fake()->createWithContent($name, $rawPng, 'image/png');
    }

    public function test_upload_foto_profil_via_multipart_file(): void
    {
        Storage::fake('public');

        $user = User::factory()->create([
            'role' => 'jaksa',
        ]);

        $file = $this->createFakeImageFile('profile.png');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/user/foto', [
            'foto_profil' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Foto profil berhasil diperbarui.',
            ]);

        $user->refresh();
        $this->assertNotNull($user->foto_profil);
        Storage::disk('public')->assertExists($user->foto_profil);

        // Pastikan URL foto profil lengkap HTTP
        $this->assertStringContainsString('/storage/profiles/', $user->foto_profil_url);
        $this->assertStringContainsString('/storage/profiles/', $user->foto_profile_url);
        $this->assertSame($user->foto_profil_url, $response->json('data.foto_profil_url'));
        $this->assertSame($user->foto_profile_url, $response->json('data.foto_profile_url'));
    }

    public function test_upload_foto_profil_via_file_alias_foto_and_route_photo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'jaksa']);
        $file = $this->createFakeImageFile('avatar.png');

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/profile/photo', [
            'photo' => $file,
        ]);

        $response->assertStatus(200);
        $user->refresh();
        $this->assertNotNull($user->foto_profil);
        Storage::disk('public')->assertExists($user->foto_profil);
    }

    public function test_upload_foto_profil_via_base64_string(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'jaksa']);

        // 1x1 transparent PNG Base64
        $base64Image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/foto-profil', [
            'foto_profil' => $base64Image,
        ]);

        $response->assertStatus(200);
        $user->refresh();
        $this->assertNotNull($user->foto_profil);
        Storage::disk('public')->assertExists($user->foto_profil);
    }

    public function test_old_foto_profil_is_deleted_on_replacement(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'jaksa']);

        // Upload 1
        $file1 = $this->createFakeImageFile('first.png');
        $this->actingAs($user, 'sanctum')->postJson('/api/user/foto', ['foto_profil' => $file1]);
        $oldPath = $user->fresh()->foto_profil;
        Storage::disk('public')->assertExists($oldPath);

        // Upload 2 (Replace)
        $file2 = $this->createFakeImageFile('second.png');
        $this->actingAs($user, 'sanctum')->postJson('/api/user/foto', ['foto_profil' => $file2]);
        $newPath = $user->fresh()->foto_profil;

        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing($oldPath);
    }
}
