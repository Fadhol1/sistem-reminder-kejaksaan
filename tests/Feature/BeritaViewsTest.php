<?php

namespace Tests\Feature;

use App\Models\Berita;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BeritaViewsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Uji bahwa endpoint API detail berita benar-benar menambah (increment) views_count di database secara nyata.
     */
    public function test_api_detail_berita_increments_views_count(): void
    {
        $user = User::factory()->create(['role' => 'jaksa']);
        $berita = Berita::create([
            'judul' => 'Uji Coba Berita Real View API',
            'slug' => 'uji-coba-berita-real-view-api',
            'ringkasan' => 'Ringkasan uji coba',
            'konten' => 'Konten lengkap berita',
            'tanggal' => now()->toDateString(),
            'penulis' => 'Humas Kejaksaan',
            'user_id' => $user->id,
            'is_published' => true,
            'views_count' => 0,
        ]);

        $this->assertEquals(0, $berita->views_count);

        // Akses pertama kali via API (misal dibuka di HP Android)
        $response1 = $this->getJson("/api/berita/{$berita->id}");
        $response1->assertOk();
        $response1->assertJsonPath('data.views_count', 1);

        $berita->refresh();
        $this->assertEquals(1, $berita->views_count);
        $this->assertDatabaseHas('beritas', [
            'id' => $berita->id,
            'views_count' => 1,
        ]);

        // Akses kedua kali via API menggunakan Slug
        $response2 = $this->getJson("/api/berita/{$berita->slug}");
        $response2->assertOk();
        $response2->assertJsonPath('data.views_count', 2);

        $berita->refresh();
        $this->assertEquals(2, $berita->views_count);
        $this->assertDatabaseHas('beritas', [
            'id' => $berita->id,
            'views_count' => 2,
        ]);
    }

    /**
     * Uji bahwa endpoint POST /api/berita/{id}/view menambah views_count secara nyata.
     */
    public function test_api_post_berita_view_increments_views_count(): void
    {
        $user = User::factory()->create(['role' => 'jaksa']);
        $berita = Berita::create([
            'judul' => 'Uji Coba POST View Berita',
            'slug' => 'uji-coba-post-view-berita',
            'ringkasan' => 'Ringkasan uji coba',
            'konten' => 'Konten lengkap berita',
            'tanggal' => now()->toDateString(),
            'penulis' => 'Humas Kejaksaan',
            'user_id' => $user->id,
            'is_published' => true,
            'views_count' => 0,
        ]);

        $response1 = $this->postJson("/api/berita/{$berita->id}/view");
        $response1->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('views_count', 1)
            ->assertJsonPath('data.views_count', 1);

        $response2 = $this->postJson("/api/berita/{$berita->slug}/view");
        $response2->assertOk()
            ->assertJsonPath('views_count', 2)
            ->assertJsonPath('data.views_count', 2);

        $this->assertDatabaseHas('beritas', [
            'id' => $berita->id,
            'views_count' => 2,
        ]);

        // Berita draf atau tidak ditemukan tetap mengembalikan 404
        $draft = Berita::create([
            'judul' => 'Berita Draf',
            'slug' => 'berita-draf',
            'tanggal' => now()->toDateString(),
            'user_id' => $user->id,
            'is_published' => false,
            'views_count' => 0,
        ]);

        $this->postJson("/api/berita/{$draft->id}/view")->assertNotFound();
        $this->postJson('/api/berita/999999/view')->assertNotFound();
    }

    /**
     * Uji bahwa preview berita pada Web Admin juga menambah views_count di database secara nyata.
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

        // Akses kedua kali pada sesi yang sama tidak menambah dua kali (proteksi spam refresh)
        $responseRepeat = $this->actingAs($user)->get(route('berita.show', $berita->id));
        $responseRepeat->assertOk();

        $berita->refresh();
        $this->assertEquals(6, $berita->views_count);
    }
}

