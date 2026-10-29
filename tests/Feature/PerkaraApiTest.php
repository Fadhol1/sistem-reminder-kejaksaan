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

    protected function setUp(): void
    {
        parent::setUp();

        $user = \App\Models\User::factory()->create([
            'role' => 'pidum',
        ]);
        \Laravel\Sanctum\Sanctum::actingAs($user);
    }

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
                     ],
                 ]);

        $this->assertDatabaseHas('perkaras', [
            'nomor_perkara' => 'BP/001/IX/2026/RESKRIM',
        ]);

        $this->assertDatabaseHas('tersangkas', [
            'perkara_id' => $response->json('data.id'),
            'nama' => 'Ahmad Santoso',
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
            'penyidik'        => 'AIPTU Joko',
            'jaksa'           => 'Siti Aminah, S.H.',
            'tanggal_spdp'    => '2026-09-01',
            'tahap_saat_ini'  => 'Tahap I',
            'status_saat_ini' => 'Penelitian Berkas Perkara',
        ]);
        $perkara->tersangkas()->create(['nama' => 'Budi Darmawan']);

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
                         'data' => [
                             '*' => [
                                 'id',
                                 'nomor_perkara',
                                 'latest_timeline',
                                 'active_reminders',
                             ],
                         ],
                     ],
                 ]);
    }

    public function test_show_perkara_returns_full_history(): void
    {
        $perkara = Perkara::create([
            'nomor_perkara'   => 'BP/003/IX/2026/RESKRIM',
            'penyidik'        => 'IPDA Surya',
            'jaksa'           => 'Diana Putri, S.H.',
            'tanggal_spdp'    => '2026-09-01',
            'tahap_saat_ini'  => 'SPDP',
            'status_saat_ini' => 'Diterima',
        ]);
        $perkara->tersangkas()->create(['nama' => 'Hendro Kusumo']);

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
            'penyidik'        => 'Penyidik Y',
            'jaksa'           => 'Jaksa Z',
            'tanggal_spdp'    => '2026-09-01',
            'tahap_saat_ini'  => 'SPDP',
            'status_saat_ini' => 'Menunggu Berkas Tahap 1',
        ]);
        $perkara->tersangkas()->create(['nama' => 'Tersangka X']);

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
            'penyidik'        => 'Penyidik A',
            'jaksa'           => 'Jaksa B',
            'tanggal_spdp'    => '2026-09-01',
            'tahap_saat_ini'  => 'Tahap 1',
            'status_saat_ini' => 'Penelitian',
        ]);
        $perkara->tersangkas()->create(['nama' => 'Tersangka Urgensi']);

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
                         'data' => [
                             '*' => [
                                 'id',
                                 'jenis_reminder',
                                 'status_urgensi',
                                 'perkara' => [
                                     'nomor_perkara',
                                 ],
                             ],
                         ],
                     ],
                 ]);
    }

    public function test_pidum_can_view_edit_perkara_page_with_jaksas_and_penyidik(): void
    {
        $jaksa1 = \App\Models\User::factory()->create(['role' => 'jaksa', 'name' => 'Jaksa Satu, S.H.']);
        $jaksa2 = \App\Models\User::factory()->create(['role' => 'jaksa', 'name' => 'Jaksa Dua, S.H.']);

        $perkara = Perkara::create([
            'nomor_perkara'   => 'BP/EDIT/01/2026',
            'penyidik'        => 'Penyidik Asli',
            'jaksa'           => $jaksa1->name,
            'tanggal_spdp'    => '2026-10-01',
            'tahap_saat_ini'  => 'Koordinasi Awal',
            'status_saat_ini' => 'Pendaftaran SPDP',
        ]);
        $perkara->jaksas()->attach([$jaksa1->id]);
        $perkara->tersangkas()->create(['nama' => 'Tersangka Lama']);

        $response = $this->get(route('perkara.edit', $perkara->id));

        $response->assertStatus(200);
        $response->assertViewIs('perkara.edit');
        $response->assertViewHas('perkara');
        $response->assertViewHas('jaksas');
        $response->assertViewHas('selectedJaksaIds', [$jaksa1->id]);
        $response->assertSee('Penyidik Asli');
        $response->assertSee('Jaksa Satu, S.H.');
        $response->assertSee('Jaksa Dua, S.H.');
    }

    public function test_pidum_can_update_perkara_with_new_penyidik_and_jaksas(): void
    {
        $jaksa1 = \App\Models\User::factory()->create(['role' => 'jaksa', 'name' => 'Jaksa Satu, S.H.']);
        $jaksa2 = \App\Models\User::factory()->create(['role' => 'jaksa', 'name' => 'Jaksa Dua, S.H.']);
        $jaksa3 = \App\Models\User::factory()->create(['role' => 'jaksa', 'name' => 'Jaksa Tiga, S.H.']);

        $perkara = Perkara::create([
            'nomor_perkara'   => 'BP/EDIT/02/2026',
            'penyidik'        => 'Penyidik Awal',
            'jaksa'           => $jaksa1->name,
            'tanggal_spdp'    => '2026-10-01',
            'tahap_saat_ini'  => 'Koordinasi Awal',
            'status_saat_ini' => 'Pendaftaran SPDP',
        ]);
        $perkara->jaksas()->attach([$jaksa1->id]);
        $perkara->tersangkas()->create(['nama' => 'Tersangka 1']);

        $payload = [
            'nomor_perkara' => 'BP/EDIT/02/2026-REVISI',
            'penyidik' => 'IPTU Hendro / Unit Tipidter Polres',
            'jaksas' => [$jaksa2->id, $jaksa3->id],
            'tersangkas' => [
                ['nama' => 'Tersangka Revisi 1'],
                ['nama' => 'Tersangka Revisi 2'],
            ],
        ];

        $response = $this->put(route('perkara.update', $perkara->id), $payload);

        $response->assertRedirect(route('perkara.show', $perkara->id));
        $response->assertSessionHas('success');

        // Verify updated database records
        $this->assertDatabaseHas('perkaras', [
            'id' => $perkara->id,
            'nomor_perkara' => 'BP/EDIT/02/2026-REVISI',
            'penyidik' => 'IPTU Hendro / Unit Tipidter Polres',
            'jaksa' => $jaksa2->name,
        ]);

        // Verify synced jaksas
        $freshPerkara = $perkara->fresh(['jaksas', 'tersangkas']);
        $this->assertEqualsCanonicalizing(
            [$jaksa2->id, $jaksa3->id],
            $freshPerkara->jaksas->pluck('id')->toArray()
        );

        // Verify synced tersangkas
        $this->assertEqualsCanonicalizing(
            ['Tersangka Revisi 1', 'Tersangka Revisi 2'],
            $freshPerkara->tersangkas->pluck('nama')->toArray()
        );
    }

    public function test_non_pidum_cannot_edit_or_update_perkara(): void
    {
        $jaksaUser = \App\Models\User::factory()->create(['role' => 'jaksa']);
        \Laravel\Sanctum\Sanctum::actingAs($jaksaUser);

        $perkara = Perkara::create([
            'nomor_perkara'   => 'BP/EDIT/03/2026',
            'penyidik'        => 'Penyidik X',
            'jaksa'           => 'Jaksa X',
            'tanggal_spdp'    => '2026-10-01',
            'tahap_saat_ini'  => 'Koordinasi Awal',
            'status_saat_ini' => 'Pendaftaran SPDP',
        ]);

        $this->get(route('perkara.edit', $perkara->id))->assertStatus(403);
        $this->put(route('perkara.update', $perkara->id), [
            'nomor_perkara' => 'BP/EDIT/03/2026',
            'penyidik' => 'Penyidik X',
            'jaksas' => [$jaksaUser->id],
            'tersangkas' => [['nama' => 'Tersangka X']],
        ])->assertStatus(403);
    }

    public function test_pidum_can_store_perkara_with_multiple_penyidiks(): void
    {
        $jaksa = \App\Models\User::factory()->create(['role' => 'jaksa']);

        $payload = [
            'nomor_perkara' => 'BP/TEST/MULTI-PENYIDIK/01',
            'tanggal_spdp' => '2026-10-28',
            'tersangkas' => [
                ['nama' => 'Tersangka Satu'],
                ['nama' => 'Tersangka Dua'],
            ],
            'penyidiks' => [
                ['nama' => 'IPTU Bambang Wijaya / Polres OKU'],
                ['nama' => 'AIPDA Supriadi / Polres OKU'],
            ],
            'jaksas' => [$jaksa->id],
        ];

        $response = $this->post(route('perkara.store'), $payload);

        $response->assertRedirect(route('perkara.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('perkaras', [
            'nomor_perkara' => 'BP/TEST/MULTI-PENYIDIK/01',
            'penyidik' => 'IPTU Bambang Wijaya / Polres OKU, AIPDA Supriadi / Polres OKU',
        ]);

        $perkara = Perkara::where('nomor_perkara', 'BP/TEST/MULTI-PENYIDIK/01')->first();
        $this->assertNotNull($perkara);
        $this->assertCount(2, $perkara->penyidiks);
        $this->assertEqualsCanonicalizing(
            ['IPTU Bambang Wijaya / Polres OKU', 'AIPDA Supriadi / Polres OKU'],
            $perkara->penyidiks->pluck('nama')->toArray()
        );
    }

    public function test_pidum_can_update_perkara_with_multiple_penyidiks(): void
    {
        $jaksa = \App\Models\User::factory()->create(['role' => 'jaksa']);

        $perkara = Perkara::create([
            'nomor_perkara'   => 'BP/EDIT/MULTI-PENYIDIK/02',
            'penyidik'        => 'Penyidik Awal',
            'jaksa'           => $jaksa->name,
            'tanggal_spdp'    => '2026-10-01',
            'tahap_saat_ini'  => 'Koordinasi Awal',
            'status_saat_ini' => 'Pendaftaran SPDP',
        ]);
        $perkara->jaksas()->attach([$jaksa->id]);
        $perkara->tersangkas()->create(['nama' => 'Tersangka 1']);
        $perkara->penyidiks()->create(['nama' => 'Penyidik Awal']);

        $payload = [
            'nomor_perkara' => 'BP/EDIT/MULTI-PENYIDIK/02',
            'penyidiks' => [
                ['nama' => 'Kompol Agus / Ditreskrimum'],
                ['nama' => 'AKP Budi / Subdit Jatanras'],
                ['nama' => 'Bripka Chandra / Penyidik Pembantu'],
            ],
            'jaksas' => [$jaksa->id],
            'tersangkas' => [
                ['nama' => 'Tersangka 1'],
            ],
        ];

        $response = $this->put(route('perkara.update', $perkara->id), $payload);

        $response->assertRedirect(route('perkara.show', $perkara->id));
        $response->assertSessionHas('success');

        $fresh = $perkara->fresh(['penyidiks']);
        $this->assertCount(3, $fresh->penyidiks);
        $this->assertEqualsCanonicalizing(
            ['Kompol Agus / Ditreskrimum', 'AKP Budi / Subdit Jatanras', 'Bripka Chandra / Penyidik Pembantu'],
            $fresh->penyidiks->pluck('nama')->toArray()
        );
        $this->assertEquals('Kompol Agus / Ditreskrimum, AKP Budi / Subdit Jatanras, Bripka Chandra / Penyidik Pembantu', $fresh->penyidik);
    }

    public function test_perkara_show_displays_all_penyidiks(): void
    {
        $jaksa = \App\Models\User::factory()->create(['role' => 'jaksa']);

        $perkara = Perkara::create([
            'nomor_perkara'   => 'BP/SHOW/MULTI/01',
            'penyidik'        => 'Penyidik Ringkasan',
            'jaksa'           => $jaksa->name,
            'tanggal_spdp'    => '2026-10-01',
            'tahap_saat_ini'  => 'Koordinasi Awal',
            'status_saat_ini' => 'Pendaftaran SPDP',
        ]);
        $perkara->jaksas()->attach([$jaksa->id]);
        $perkara->tersangkas()->create(['nama' => 'Tersangka Utama']);
        $perkara->penyidiks()->create(['nama' => 'Penyidik Kesatu / Reskrim']);
        $perkara->penyidiks()->create(['nama' => 'Penyidik Kedua / Reskrim']);

        $response = $this->get(route('perkara.show', $perkara->id));

        $response->assertStatus(200);
        $response->assertSee('Daftar Penyidik');
        $response->assertSee('Penyidik Kesatu / Reskrim');
        $response->assertSee('Penyidik Kedua / Reskrim');
    }

    public function test_pidum_can_view_create_perkara_page_with_multiple_penyidik_inputs(): void
    {
        $response = $this->get(route('perkara.create'));

        $response->assertStatus(200);
        $response->assertSee('Daftar Penyidik / Instansi Asal');
        $response->assertSee('penyidiks[0][nama]');
        $response->assertSee('addPenyidikRow');
        $response->assertSee('removePenyidikRow');
        $response->assertSee('Tambah Penyidik');
    }

    public function test_pidum_cannot_view_edit_page_when_koordinasi_awal_is_overdue(): void
    {
        $jaksa = \App\Models\User::factory()->create(['role' => 'jaksa']);

        $perkara = Perkara::create([
            'nomor_perkara'   => 'BP/OVERDUE/EDIT/01',
            'penyidik'        => 'Penyidik Polsek',
            'jaksa'           => $jaksa->name,
            'tanggal_spdp'    => '2026-10-01',
            'tahap_saat_ini'  => 'Koordinasi Awal',
            'status_saat_ini' => 'Overdue / Tindak Lanjut SOP Form-1B & Form-1C',
        ]);
        $perkara->jaksas()->attach([$jaksa->id]);

        $response = $this->get(route('perkara.edit', $perkara->id));

        $response->assertStatus(403);
    }

    public function test_pidum_cannot_update_perkara_when_koordinasi_awal_is_overdue(): void
    {
        $jaksa = \App\Models\User::factory()->create(['role' => 'jaksa']);

        $perkara = Perkara::create([
            'nomor_perkara'   => 'BP/OVERDUE/UPDATE/02',
            'penyidik'        => 'Penyidik Asli',
            'jaksa'           => $jaksa->name,
            'tanggal_spdp'    => '2026-10-01',
            'tahap_saat_ini'  => 'Koordinasi Awal',
            'status_saat_ini' => 'Menunggu Koordinasi',
        ]);
        $perkara->jaksas()->attach([$jaksa->id]);
        $perkara->tersangkas()->create(['nama' => 'Tersangka Asli']);
        $perkara->penyidiks()->create(['nama' => 'Penyidik Asli']);

        // Create overdue active reminder
        $perkara->reminders()->create([
            'tahap'          => 'Koordinasi Awal',
            'jenis_reminder' => 'SLA Koordinasi Awal',
            'tanggal_mulai'  => '2026-10-01',
            'deadline'       => '2026-10-04',
            'status_urgensi' => 'Overdue',
            'is_active'      => true,
        ]);

        $payload = [
            'nomor_perkara' => 'BP/OVERDUE/UPDATE/02-HACK',
            'penyidiks'     => [['nama' => 'Penyidik Baru']],
            'jaksas'        => [$jaksa->id],
            'tersangkas'    => [['nama' => 'Tersangka Baru']],
        ];

        $response = $this->put(route('perkara.update', $perkara->id), $payload);

        $response->assertStatus(403);

        // Verify database is NOT updated
        $this->assertDatabaseHas('perkaras', [
            'id'            => $perkara->id,
            'nomor_perkara' => 'BP/OVERDUE/UPDATE/02',
            'penyidik'      => 'Penyidik Asli',
        ]);
    }

    public function test_api_store_timeline_rejects_update_when_koordinasi_awal_is_overdue(): void
    {
        $perkara = Perkara::create([
            'nomor_perkara'   => 'BP/API/OVERDUE/03',
            'penyidik'        => 'Penyidik Asli',
            'jaksa'           => 'Jaksa Asli',
            'tanggal_spdp'    => '2026-10-01',
            'tahap_saat_ini'  => 'Koordinasi Awal',
            'status_saat_ini' => 'Overdue / Tindak Lanjut SOP Form-1B & Form-1C',
        ]);

        $response = $this->postJson('/api/perkara/timeline', [
            'perkara_id'            => $perkara->id,
            'tahap'                 => 'Monitoring Penyidikan',
            'status_kegiatan'       => 'Penerimaan Berkas',
            'tanggal_kejadian'      => '2026-10-29',
            'update_status_perkara' => true,
        ]);

        $response->assertStatus(403);
    }

    public function test_catat_perkembangan_is_rejected_when_koordinasi_awal_is_overdue(): void
    {
        $perkara = Perkara::create([
            'nomor_perkara'   => 'BP/OVERDUE/CATAT/01',
            'penyidik'        => 'Penyidik Polsek',
            'jaksa'           => 'Jaksa Peneliti',
            'tanggal_spdp'    => '2026-10-01',
            'tahap_saat_ini'  => 'Koordinasi Awal',
            'status_saat_ini' => 'Overdue / Tindak Lanjut SOP Form-1B & Form-1C',
        ]);

        $response = $this->post(route('perkara.catatPerkembangan', $perkara->id), [
            'action_id' => 'koordinasi_selesai',
            'tanggal'   => '2026-10-29',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_perkara_show_displays_catat_perkembangan_disabled_when_overdue(): void
    {
        $jaksa = \App\Models\User::factory()->create(['role' => 'jaksa']);

        $perkara = Perkara::create([
            'nomor_perkara'   => 'BP/SHOW/OVERDUE/02',
            'penyidik'        => 'Penyidik Polres',
            'jaksa'           => $jaksa->name,
            'tanggal_spdp'    => '2026-10-01',
            'tahap_saat_ini'  => 'Koordinasi Awal',
            'status_saat_ini' => 'Menunggu Koordinasi',
        ]);
        $perkara->jaksas()->attach([$jaksa->id]);

        $perkara->reminders()->create([
            'tahap'          => 'Koordinasi Awal',
            'jenis_reminder' => 'SLA Koordinasi Awal',
            'tanggal_mulai'  => '2026-10-01',
            'deadline'       => '2026-10-04',
            'status_urgensi' => 'Overdue',
            'is_active'      => true,
        ]);

        $response = $this->get(route('perkara.show', $perkara->id));

        $response->assertStatus(200);
        $response->assertSee('Pencatatan Perkembangan Dinonaktifkan');
        $response->assertSee('Terkunci (Overdue)');
        $response->assertDontSee('Simpan Perkembangan');
    }

    public function test_workflow_service_get_available_actions_returns_empty_when_koordinasi_awal_is_overdue(): void
    {
        $perkara = Perkara::create([
            'nomor_perkara'   => 'BP/WORKFLOW/OVERDUE/03',
            'penyidik'        => 'Penyidik Polres',
            'jaksa'           => 'Jaksa Peneliti',
            'tanggal_spdp'    => '2026-10-01',
            'tahap_saat_ini'  => 'Koordinasi Awal',
            'status_saat_ini' => 'Overdue / Tindak Lanjut SOP Form-1B & Form-1C',
        ]);

        $actions = \App\Services\PerkaraWorkflowService::getAvailableActions($perkara);
        $this->assertEmpty($actions);
    }

    public function test_reminder_and_perkara_dates_are_serialized_as_ymd_without_timezone_day_shift(): void
    {
        $perkara = Perkara::create([
            'nomor_perkara'   => 'BP/TZ/TEST/01',
            'penyidik'        => 'Penyidik Polres',
            'jaksa'           => 'Jaksa Peneliti',
            'tanggal_spdp'    => '2026-10-29',
            'tahap_saat_ini'  => 'Koordinasi Awal',
            'status_saat_ini' => 'Koordinasi Awal',
        ]);

        $reminder = $perkara->reminders()->create([
            'jenis_reminder' => 'SLA Koordinasi Awal',
            'tanggal_mulai'  => '2026-10-26',
            'deadline'       => '2026-10-29',
            'status_urgensi' => 'Jatuh Tempo',
            'is_active'      => true,
        ]);

        $serializedReminder = $reminder->toArray();
        $this->assertEquals('2026-10-29', $serializedReminder['deadline']);
        $this->assertEquals('2026-10-26', $serializedReminder['tanggal_mulai']);
        $this->assertEquals(0, $serializedReminder['sisa_hari']);
        $this->assertEquals('Jatuh Tempo', $serializedReminder['status_urgensi']);

        $serializedPerkara = $perkara->toArray();
        $this->assertEquals('2026-10-29', $serializedPerkara['tanggal_spdp']);
    }
}

