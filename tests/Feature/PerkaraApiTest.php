<?php

namespace Tests\Feature;

use App\Models\Perkara;
use App\Models\PerkaraReminder;
use App\Models\PerkaraTimeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerkaraApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_perkara_creates_record_and_initial_timeline_and_reminder(): void
    {
        $payload = [
            'nomor_perkara'   => 'BP/001/IX/2026/RESKRIM',
            'nama_tersangka'  => 'Ahmad Santoso',
            'penyidik'        => 'IPTU Bambang Wijaya',
            'jaksa'           => 'Rudi Hermawan, S.H.',
            'tanggal_spdp'    => '2026-09-10',
            'tahap_saat_ini'  => 'Pra-Penuntutan (SPDP)',
            'status_saat_ini' => 'Penerimaan SPDP',
            'catatan'         => 'SPDP diterima langsung di PTSP Kejaksaan.',
            'buat_reminder'   => true,
        ];

        $response = $this->postJson('/api/perkara', $payload);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'nomor_perkara'  => 'BP/001/IX/2026/RESKRIM',
                         'nama_tersangka' => 'Ahmad Santoso',
                     ],
                 ]);

        $this->assertDatabaseHas('perkaras', [
            'nomor_perkara' => 'BP/001/IX/2026/RESKRIM',
        ]);

        $this->assertDatabaseHas('perkara_timelines', [
            'tahap'           => 'Pra-Penuntutan (SPDP)',
            'status_kegiatan' => 'Penerimaan SPDP',
        ]);

        $this->assertDatabaseHas('perkara_reminders', [
            'is_active' => true,
        ]);
    }

    public function test_index_perkara_returns_list_with_latest_timeline_and_active_reminders(): void
    {
        $perkara = Perkara::create([
            'nomor_perkara'   => 'BP/002/IX/2026/RESKRIM',
            'nama_tersangka'  => 'Budi Darmawan',
            'penyidik'        => 'AIPTU Joko',
            'jaksa'           => 'Siti Aminah, S.H.',
            'tanggal_spdp'    => '2026-09-01',
            'tahap_saat_ini'  => 'Tahap I',
            'status_saat_ini' => 'Penelitian Berkas Perkara',
        ]);

        $perkara->timelines()->create([
            'tahap'            => 'Tahap I',
            'status_kegiatan'  => 'Penelitian Berkas Perkara',
            'tanggal_kejadian' => '2026-09-05',
            'catatan'          => 'Berkas tahap 1 diterima dari penyidik.',
        ]);

        $perkara->reminders()->create([
            'jenis_reminder' => 'P-18 / P-19 (SLA 7/14 Hari)',
            'tanggal_mulai'  => '2026-09-05',
            'deadline'       => '2026-09-19',
            'status_urgensi' => 'Overdue',
            'is_active'      => true,
        ]);

        $response = $this->getJson('/api/perkara');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'nomor_perkara',
                             'nama_tersangka',
                             'latest_timeline',
                             'active_reminders',
                         ],
                     ],
                 ]);
    }

    public function test_show_perkara_returns_full_history(): void
    {
        $perkara = Perkara::create([
            'nomor_perkara'   => 'BP/003/IX/2026/RESKRIM',
            'nama_tersangka'  => 'Hendro Kusumo',
            'penyidik'        => 'IPDA Surya',
            'jaksa'           => 'Diana Putri, S.H.',
            'tanggal_spdp'    => '2026-09-01',
            'tahap_saat_ini'  => 'SPDP',
            'status_saat_ini' => 'Diterima',
        ]);

        $perkara->timelines()->create([
            'tahap'            => 'SPDP',
            'status_kegiatan'  => 'Diterima',
            'tanggal_kejadian' => '2026-09-01',
        ]);

        $perkara->reminders()->create([
            'jenis_reminder' => 'P-17',
            'tanggal_mulai'  => '2026-09-01',
            'deadline'       => '2026-10-01',
            'status_urgensi' => 'Aman',
            'is_active'      => true,
        ]);

        $response = $this->getJson("/api/perkara/{$perkara->id}");

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'id'            => $perkara->id,
                         'nomor_perkara' => 'BP/003/IX/2026/RESKRIM',
                     ],
                 ])
                 ->assertJsonStructure([
                     'data' => [
                         'timelines',
                         'reminders',
                         'active_reminders',
                     ],
                 ]);
    }

    public function test_store_timeline_updates_perkara_and_manages_reminder(): void
    {
        $perkara = Perkara::create([
            'nomor_perkara'   => 'BP/004/IX/2026/RESKRIM',
            'nama_tersangka'  => 'Tersangka X',
            'penyidik'        => 'Penyidik Y',
            'jaksa'           => 'Jaksa Z',
            'tanggal_spdp'    => '2026-09-01',
            'tahap_saat_ini'  => 'SPDP',
            'status_saat_ini' => 'Menunggu Berkas Tahap 1',
        ]);

        $oldReminder = $perkara->reminders()->create([
            'jenis_reminder' => 'P-17',
            'tanggal_mulai'  => '2026-09-01',
            'deadline'       => '2026-10-01',
            'status_urgensi' => 'Aman',
            'is_active'      => true,
        ]);

        // Kirim update timeline: Berkas Tahap 1 diterima, buat reminder SLA P-18/P-21 baru
        $timelinePayload = [
            'tahap'                    => 'Tahap 1',
            'status_kegiatan'          => 'Penerimaan Berkas Tahap 1',
            'tanggal_kejadian'         => '2026-09-15',
            'catatan'                  => 'Penyidik menyerahkan berkas tahap 1 untuk diteliti.',
            'create_reminder'          => true,
            'close_previous_reminders' => true,
            'jenis_reminder'           => 'Penelitian Berkas (SLA 7 Hari / P-18)',
            'deadline'                 => '2026-09-22',
        ];

        $response = $this->postJson("/api/perkara/{$perkara->id}/timeline", $timelinePayload);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'perkara' => [
                             'tahap_saat_ini'  => 'Tahap 1',
                             'status_saat_ini' => 'Penerimaan Berkas Tahap 1',
                         ],
                     ],
                 ]);

        // Verifikasi database
        $this->assertDatabaseHas('perkaras', [
            'id'              => $perkara->id,
            'tahap_saat_ini'  => 'Tahap 1',
            'status_saat_ini' => 'Penerimaan Berkas Tahap 1',
        ]);

        // Reminder lama harus dinonaktifkan
        $this->assertFalse($oldReminder->fresh()->is_active);

        // Reminder baru harus aktif
        $this->assertDatabaseHas('perkara_reminders', [
            'perkara_id'     => $perkara->id,
            'jenis_reminder' => 'Penelitian Berkas (SLA 7 Hari / P-18)',
            'is_active'      => true,
        ]);
    }

    public function test_get_reminders_returns_active_urgent_reminders_and_dashboard_summary(): void
    {
        $perkara = Perkara::create([
            'nomor_perkara'   => 'BP/005/IX/2026/RESKRIM',
            'nama_tersangka'  => 'Tersangka Urgensi',
            'penyidik'        => 'Penyidik A',
            'jaksa'           => 'Jaksa B',
            'tanggal_spdp'    => '2026-09-01',
            'tahap_saat_ini'  => 'Tahap 1',
            'status_saat_ini' => 'Penelitian',
        ]);

        $perkara->reminders()->create([
            'jenis_reminder' => 'P-19 (Pengembalian Berkas)',
            'tanggal_mulai'  => '2026-09-01',
            'deadline'       => '2026-09-15',
            'status_urgensi' => 'Overdue',
            'is_active'      => true,
        ]);

        $perkara->reminders()->create([
            'jenis_reminder' => 'P-21',
            'tanggal_mulai'  => '2026-09-01',
            'deadline'       => '2026-09-23',
            'status_urgensi' => 'H-3',
            'is_active'      => true,
        ]);

        $response = $this->getJson('/api/reminders?urgent_only=1');

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                 ])
                 ->assertJsonStructure([
                     'summary' => [
                         'total_active',
                         'total_urgent',
                         'overdue',
                         'jatuh_tempo',
                         'h_min_3',
                         'aman',
                     ],
                     'data' => [
                         '*' => [
                             'id',
                             'jenis_reminder',
                             'status_urgensi',
                             'perkara' => [
                                 'nomor_perkara',
                                 'nama_tersangka',
                             ],
                         ],
                     ],
                 ]);
    }
}
