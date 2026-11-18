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

    public function test_base64_upload_rejects_malicious_html_svg_php_and_spoofed_png(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'jaksa']);

        $maliciousPayloads = [
            'data:image/html;base64,' . base64_encode('<script>alert("xss")</script>'),
            'data:image/svg;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>'),
            'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"/>'),
            'data:image/php;base64,' . base64_encode('<?php system("id"); ?>'),
            // Disguised HTML pretending to be PNG
            'data:image/png;base64,' . base64_encode('<script>alert("spoofed")</script>'),
            // Raw non-image base64 without prefix
            base64_encode('<html><body>not an image</body></html>'),
        ];

        foreach ($maliciousPayloads as $payload) {
            $response = $this->actingAs($user, 'sanctum')->postJson('/api/foto-profil', [
                'foto_profil' => $payload,
            ]);

            $response->assertStatus(422);
        }

        $user->refresh();
        $this->assertNull($user->foto_profil);
        $this->assertEmpty(Storage::disk('public')->allFiles('profiles'));
    }

    public function test_update_profile_rejects_non_image_uploaded_via_file_alias(): void
    {
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'jaksa']);
        $maliciousFile = UploadedFile::fake()->createWithContent(
            'exploit.html',
            '<script>alert("xss")</script>',
            'text/html'
        );

        foreach (['avatar', 'file', 'foto', 'photo', 'image'] as $alias) {
            $response = $this->actingAs($user, 'sanctum')->postJson('/api/profile', [
                $alias => $maliciousFile,
            ]);

            $response->assertStatus(422);
        }

        $user->refresh();
        $this->assertNull($user->foto_profil);
        $this->assertEmpty(Storage::disk('public')->allFiles('profiles'));
    }
}
