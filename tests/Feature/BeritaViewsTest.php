<?php

namespace Tests\Feature;

use App\Models\Berita;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BeritaViewsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Uji bahwa endpoint API detail berita hanya menambah views_count 1x per user/perangkat unik.
     */
    public function test_api_detail_berita_increments_views_count_once_per_viewer(): void
    {
        $user1 = User::factory()->create(['role' => 'jaksa']);
        $user2 = User::factory()->create(['role' => 'jaksa']);

        $berita = Berita::create([
            'judul' => 'Uji Coba Berita Real View API',
            'slug' => 'uji-coba-berita-real-view-api',
            'ringkasan' => 'Ringkasan uji coba',
            'konten' => 'Konten lengkap berita',
            'tanggal' => now()->toDateString(),
            'penulis' => 'Humas Kejaksaan',
            'user_id' => $user1->id,
            'is_published' => true,
            'views_count' => 0,
        ]);

        // Jaksa 1 membuka detail pertama kali -> bertambah jadi 1
        Sanctum::actingAs($user1);
        $this->getJson("/api/berita/{$berita->id}")
            ->assertOk()
            ->assertJsonPath('data.views_count', 1);

        // Jaksa 1 membuka ulang via slug -> tetap 1 (tidak bertambah)
        $this->getJson("/api/berita/{$berita->slug}")
            ->assertOk()
            ->assertJsonPath('data.views_count', 1);

        // Jaksa 2 membuka berita yang sama -> bertambah jadi 2
        Sanctum::actingAs($user2);
        $this->getJson("/api/berita/{$berita->id}")
            ->assertOk()
            ->assertJsonPath('data.views_count', 2);

        $this->assertDatabaseHas('beritas', [
            'id' => $berita->id,
            'views_count' => 2,
        ]);
    }

    /**
     * Uji bahwa endpoint POST /api/berita/{id}/view menghitung unik per user dan tidak double-count dengan GET detail.
     */
    public function test_api_post_berita_view_deduplicates_repeat_clicks_per_user_and_device(): void
    {
        $jaksa1 = User::factory()->create(['role' => 'jaksa']);
        $jaksa2 = User::factory()->create(['role' => 'jaksa']);

        $berita = Berita::create([
            'judul' => 'Uji Coba POST View Berita',
            'slug' => 'uji-coba-post-view-berita',
            'ringkasan' => 'Ringkasan uji coba',
            'konten' => 'Konten lengkap berita',
            'tanggal' => now()->toDateString(),
            'penulis' => 'Humas Kejaksaan',
            'user_id' => $jaksa1->id,
            'is_published' => true,
            'views_count' => 0,
        ]);

        // Jaksa 1 klik berita pertama kali -> 1
        Sanctum::actingAs($jaksa1);
        $this->postJson("/api/berita/{$berita->id}/view")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('views_count', 1)
            ->assertJsonPath('data.views_count', 1);

        // Jaksa 1 klik lagi berkali-kali (by ID, by slug, maupun GET detail) -> tetap 1
        $this->postJson("/api/berita/{$berita->id}/view")
            ->assertOk()
            ->assertJsonPath('views_count', 1);

        $this->postJson("/api/berita/{$berita->slug}/view")
            ->assertOk()
            ->assertJsonPath('views_count', 1);

        $this->getJson("/api/berita/{$berita->id}")
            ->assertOk()
            ->assertJsonPath('data.views_count', 1);

        // Jaksa 2 klik berita yang sama -> 2, klik lagi -> tetap 2
        Sanctum::actingAs($jaksa2);
        $this->postJson("/api/berita/{$berita->id}/view")
            ->assertOk()
            ->assertJsonPath('views_count', 2);

        $this->postJson("/api/berita/{$berita->id}/view")
            ->assertOk()
            ->assertJsonPath('views_count', 2);

        $this->assertDatabaseHas('beritas', [
            'id' => $berita->id,
            'views_count' => 2,
        ]);

        // Pengunjung tamu (tanpa token) dengan device_id yang sama hanya dihitung 1x
        $this->app['auth']->forgetGuards();
        $this->postJson("/api/berita/{$berita->id}/view", ['device_id' => 'hp_tamu_01'])
            ->assertOk()
            ->assertJsonPath('views_count', 3);

        $this->postJson("/api/berita/{$berita->id}/view", ['device_id' => 'hp_tamu_01'])
            ->assertOk()
            ->assertJsonPath('views_count', 3);

        // Berita draf atau tidak ditemukan tetap mengembalikan 404
        $draft = Berita::create([
            'judul' => 'Berita Draf',
            'slug' => 'berita-draf',
            'tanggal' => now()->toDateString(),
            'user_id' => $jaksa1->id,
            'is_published' => false,
            'views_count' => 0,
        ]);

        $this->postJson("/api/berita/{$draft->id}/view")->assertNotFound();
        $this->postJson('/api/berita/999999/view')->assertNotFound();
    }

    /**
     * Uji bahwa preview berita pada Web Admin hanya menambah views_count 1x per user.
     */
    public function test_web_detail_berita_increments_views_count(): void
    {
        $user = User::factory()->create(['role' => 'jaksa']);
        $berita = Berita::create([
            'judul' => 'Uji Coba Berita Real View Web',
            'slug' => 'uji-coba-berita-real-view-web',
            'ringkasan' => 'Ringkasan uji coba',
            'konten' => 'Konten lengkap berita',
            'tanggal' => now()->toDateString(),
            'penulis' => 'Humas Kejaksaan',
            'user_id' => $user->id,
            'is_published' => true,
            'views_count' => 5,
        ]);

        // Akses pertama kali via web
        $response = $this->actingAs($user)->get(route('berita.show', $berita->id));
        $response->assertOk();
        $response->assertSee('6 kali');

        $berita->refresh();
        $this->assertEquals(6, $berita->views_count);
        $this->assertDatabaseHas('beritas', [
            'id' => $berita->id,
            'views_count' => 6,
        ]);

        // Akses kedua kali oleh user yang sama tidak menambah dua kali
        $responseRepeat = $this->actingAs($user)->get(route('berita.show', $berita->id));
        $responseRepeat->assertOk();

        $berita->refresh();
        $this->assertEquals(6, $berita->views_count);
    }
}
