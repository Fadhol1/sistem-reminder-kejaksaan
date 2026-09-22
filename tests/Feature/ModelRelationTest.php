<?php

namespace Tests\Feature;

use App\Models\Perkara;
use App\Models\PerkaraReminder;
use App\Models\PerkaraTimeline;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_and_relationships(): void
    {
        $jaksa = User::create([
            'name' => 'Jaksa Agung Pratama',
            'email' => 'jaksa@kejaksaan.go.id',
            'password' => 'secret123',
            'role' => 'jaksa',
        ]);

        $penyidik = User::create([
            'name' => 'Penyidik Handoko',
            'email' => 'penyidik@polri.go.id',
            'password' => 'secret123',
            'role' => 'penyidik',
        ]);

        $this->assertTrue($jaksa->isJaksa());
        $this->assertTrue($penyidik->isPenyidik());

        $perkara = Perkara::create([
            'nomor_perkara' => 'BP/01/IX/2026/RESKRIM',
            'nama_tersangka' => 'John Doe',
            'penyidik' => $penyidik->name,
            'jaksa' => $jaksa->name,
            'tanggal_spdp' => '2026-09-01',
            'tahap_saat_ini' => 'Pra-Penuntutan',
            'status_saat_ini' => 'Pemberitahuan Dimulainya Penyidikan (SPDP)',
        ]);

        $timeline = $perkara->timelines()->create([
            'tahap' => 'SPDP Diterima',
            'status_kegiatan' => 'Penerimaan Berkas SPDP',
            'tanggal_kejadian' => '2026-09-02',
            'catatan' => 'Berkas SPDP diterima lengkap dari penyidik.',
        ]);

        $reminder = $perkara->reminders()->create([
            'jenis_reminder' => 'P-17',
            'tanggal_mulai' => '2026-09-02',
            'deadline' => '2026-09-16',
            'status_urgensi' => 'H-3',
            'is_active' => true,
        ]);

        $this->assertEquals(1, $perkara->timelines()->count());
        $this->assertEquals(1, $perkara->reminders()->count());
        $this->assertEquals($perkara->id, $timeline->perkara->id);
        $this->assertEquals($perkara->id, $reminder->perkara->id);

        $this->assertEquals(1, PerkaraReminder::active()->count());
        $this->assertEquals(1, PerkaraReminder::urgent()->count());

        // Test cascading delete
        $perkara->delete();
        $this->assertEquals(0, PerkaraTimeline::count());
        $this->assertEquals(0, PerkaraReminder::count());
    }
}
