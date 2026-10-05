<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Perkara;
use App\Models\PerkaraReminder;
use App\Models\PerkaraTimeline;
use App\Models\User;
use App\Services\PerkaraWorkflowService;
use App\Services\SLAEngineService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class TestEngine extends Command
{
    protected $signature = 'sipeta:test-engine';
    protected $description = 'Test SLA Engine';

    public function handle()
    {
        try {
            $results = [];

            $jaksaA = User::firstOrCreate(['email' => 'jaksa.a@test.com'], [
                'name' => 'Jaksa A',
                'password' => bcrypt('password'),
                'role' => 'jaksa'
            ]);
            $jaksaB = User::firstOrCreate(['email' => 'jaksa.b@test.com'], [
                'name' => 'Jaksa B',
                'password' => bcrypt('password'),
                'role' => 'jaksa'
            ]);

            Perkara::where('nomor_perkara', 'like', 'TEST-%')->delete();

            $createPerkara = function($suffix, $useJaksaB = false) use ($jaksaA, $jaksaB) {
                $perkara = Perkara::create([
                    'nomor_perkara' => 'TEST-' . $suffix,
                    'tanggal_spdp' => now(),
                    'jaksa' => $useJaksaB ? $jaksaB->name : $jaksaA->name,
                    'penyidik' => 'Penyidik Dummy',
                    'tahap_saat_ini' => PerkaraWorkflowService::TAHAP_KOORDINASI,
                    'status_saat_ini' => 'Menunggu Koordinasi',
                ]);
                
                // MANY TO MANY Binding
                $perkara->jaksas()->attach($useJaksaB ? $jaksaB->id : $jaksaA->id);

                PerkaraWorkflowService::createReminder($perkara, PerkaraWorkflowService::TAHAP_KOORDINASI, 'Batas Koordinasi Tiga Hari', 3);
                PerkaraTimeline::create([
                    'perkara_id' => $perkara->id,
                    'user_id' => $useJaksaB ? $jaksaB->id : $jaksaA->id,
                    'actor_type' => 'USER',
                    'tahap' => PerkaraWorkflowService::TAHAP_KOORDINASI,
                    'status_kegiatan' => 'Penerimaan SPDP Baru',
                    'tanggal_kejadian' => now(),
                ]);
                return $perkara;
            };

            $slaEngine = new SLAEngineService();

            // TEST 1
            $p1 = $createPerkara('001');
            $r1 = PerkaraReminder::where('perkara_id', $p1->id)->active()->first();
            $results[] = [
                'Test' => 'TEST 1 - PERKARA BARU',
                'Expected' => 'Tahap: Koordinasi Awal, Reminder: Aman',
                'Actual' => "Tahap: {$p1->tahap_saat_ini}, Reminder: {$r1->status_urgensi}",
                'Status' => ($p1->tahap_saat_ini === 'Koordinasi Awal' && $r1->status_urgensi === 'Aman') ? 'PASS' : 'FAIL'
            ];

            // TEST 2
            $p2 = $createPerkara('002');
            $r2 = PerkaraReminder::where('perkara_id', $p2->id)->active()->first();
            $r2->deadline = now()->addDays(3);
            $r2->save();
            $r2->syncStatusUrgensi();
            $results[] = [
                'Test' => 'TEST 2 - H-3',
                'Expected' => 'Urgensi: H-3, No transition',
                'Actual' => "Urgensi: {$r2->status_urgensi}, Tahap: {$p2->fresh()->tahap_saat_ini}",
                'Status' => ($r2->status_urgensi === 'H-3' && $p2->fresh()->tahap_saat_ini === 'Koordinasi Awal') ? 'PASS' : 'FAIL'
            ];

            // TEST 3
            $p3 = $createPerkara('003');
            $r3 = PerkaraReminder::where('perkara_id', $p3->id)->active()->first();
            $r3->deadline = now();
            $r3->save();
            $r3->syncStatusUrgensi();
            $results[] = [
                'Test' => 'TEST 3 - H-0',
                'Expected' => 'Urgensi: Jatuh Tempo, No transition',
                'Actual' => "Urgensi: {$r3->status_urgensi}, Tahap: {$p3->fresh()->tahap_saat_ini}",
                'Status' => ($r3->status_urgensi === 'Jatuh Tempo' && $p3->fresh()->tahap_saat_ini === 'Koordinasi Awal') ? 'PASS' : 'FAIL'
            ];

            // TEST 4
            $p4 = $createPerkara('004');
            $r4 = PerkaraReminder::where('perkara_id', $p4->id)->active()->first();
            $r4->deadline = now()->subDays(1);
            $r4->save();
            $slaEngine->executeEngine();
            $r4->refresh();
            $results[] = [
                'Test' => 'TEST 4 - OVERDUE (NEEDS CONFIRMATION)',
                'Expected' => 'Urgensi: Overdue, Tahap: Koordinasi Awal, Not Closed',
                'Actual' => "Urgensi: {$r4->status_urgensi}, Tahap: {$p4->fresh()->tahap_saat_ini}, Closed: " . ($r4->is_active ? 'No' : 'Yes'),
                'Status' => ($r4->status_urgensi === 'Overdue' && $p4->fresh()->tahap_saat_ini === 'Koordinasi Awal' && $r4->is_active === true) ? 'PASS' : 'FAIL'
            ];

            // TEST 5
            $p5 = $createPerkara('005');
            $p5->update(['tahap_saat_ini' => 'Monitoring Penyidikan', 'status_saat_ini' => 'P-17']);
            PerkaraReminder::where('perkara_id', $p5->id)->delete();
            PerkaraWorkflowService::createReminder($p5, 'Monitoring Penyidikan', 'Tindak lanjut P-17', -2); 
            $r5 = PerkaraReminder::where('perkara_id', $p5->id)->active()->first();
            $r5->syncStatusUrgensi();
            $slaEngine->executeEngine();
            $p5->refresh();
            $r5->refresh();
            $tl5 = PerkaraTimeline::where('perkara_id', $p5->id)->orderBy('id', 'desc')->first();
            $r5New = PerkaraReminder::where('perkara_id', $p5->id)->active()->first();
            $passP17 = $p5->status_saat_ini === 'P-17 Kedua / FORM-2' && $r5->is_active === false &&
                       $r5New->jenis_reminder === 'Penataan setelah FORM-2' && $tl5->actor_type === 'SYSTEM' && 
                       $tl5->user_id === null;
            $results[] = [
                'Test' => 'TEST 5 - AUTOMATIC TRANSITION P-17',
                'Expected' => 'Status: P-17 Kedua / FORM-2, Actor: SYSTEM, UserID: null',
                'Actual' => "Status: {$p5->status_saat_ini}, Actor: {$tl5->actor_type}, Reminder Lama Tertutup: " . ($r5->is_active ? 'Failed' : 'Success'),
                'Status' => $passP17 ? 'PASS' : 'FAIL'
            ];

            // TEST 6
            $tlCountBefore = PerkaraTimeline::where('perkara_id', $p5->id)->count();
            $reminderCountBefore = PerkaraReminder::where('perkara_id', $p5->id)->count();
            $slaEngine->executeEngine();
            $tlCountAfter = PerkaraTimeline::where('perkara_id', $p5->id)->count();
            $reminderCountAfter = PerkaraReminder::where('perkara_id', $p5->id)->count();
            $results[] = [
                'Test' => 'TEST 6 - IDEMPOTENCY',
                'Expected' => 'Timeline & Reminder Count is unchanged',
                'Actual' => "TL: $tlCountBefore -> $tlCountAfter | Reminders: $reminderCountBefore -> $reminderCountAfter",
                'Status' => ($tlCountBefore === $tlCountAfter && $reminderCountBefore === $reminderCountAfter) ? 'PASS' : 'FAIL'
            ];

            // TEST 7
            $p7 = $createPerkara('007');
            $p7->update(['tahap_saat_ini' => 'Monitoring Penyidikan', 'status_saat_ini' => 'P-17']);
            PerkaraReminder::where('perkara_id', $p7->id)->delete();
            PerkaraWorkflowService::createReminder($p7, 'Monitoring Penyidikan', 'Tindak lanjut P-17', 20); 
            $r7 = PerkaraReminder::where('perkara_id', $p7->id)->active()->first();
            PerkaraWorkflowService::processAction($p7, 'catat_p17_2', [], $jaksaA->id, 'USER');
            $p7->refresh();
            $r7->refresh();
            $tl7 = PerkaraTimeline::where('perkara_id', $p7->id)->orderBy('id', 'desc')->first();
            $results[] = [
                'Test' => 'TEST 7 - USER ACTION SEBELUM DEADLINE',
                'Expected' => 'Actor: USER, status: P-17 Kedua / FORM-2',
                'Actual' => "Actor: {$tl7->actor_type}, Status: {$p7->status_saat_ini}, UserID: {$tl7->user_id}",
                'Status' => ($tl7->actor_type === 'USER' && $tl7->user_id === $jaksaA->id && $p7->status_saat_ini === 'P-17 Kedua / FORM-2') ? 'PASS' : 'FAIL'
            ];

            // TEST 8
            $p8 = $createPerkara('008');
            $p8->update(['tahap_saat_ini' => 'Monitoring Penyidikan', 'status_saat_ini' => 'P-17']);
            $actionsP8 = array_column(PerkaraWorkflowService::getAvailableActions($p8), 'id');
            $results[] = [
                'Test' => 'TEST 8 - INVALID ACTION',
                'Expected' => 'P-21 (hasil_lengkap) is not available for P-17',
                'Actual' => "Available: " . implode(',', $actionsP8),
                'Status' => (!in_array('hasil_lengkap', $actionsP8) && in_array('catat_p17_2', $actionsP8)) ? 'PASS' : 'FAIL'
            ];

            // TEST 9
            $pA = $createPerkara('009A');
            $pB = $createPerkara('009B');
            $pB->update(['jaksa' => $jaksaB->name]); 
            $queryJaksaA = PerkaraReminder::whereHas('perkara', function($q) use ($jaksaA) {
                $q->where('jaksa', $jaksaA->name);
            })->whereIn('perkara_id', [$pA->id, $pB->id])->get();
            $results[] = [
                'Test' => 'TEST 9 - AUTHORIZATION JAKSA',
                'Expected' => 'Jaksa A only sees 009A',
                'Actual' => "Sees: " . $queryJaksaA->first()->perkara->nomor_perkara,
                'Status' => ($queryJaksaA->count() === 1 && $queryJaksaA->first()->perkara_id === $pA->id) ? 'PASS' : 'FAIL'
            ];

            // TEST 10
            $p10L = $createPerkara('010L');
            $p10L->update(['tahap_saat_ini' => 'P-24', 'status_saat_ini' => 'Menunggu Penelitian Berkas']);
            PerkaraWorkflowService::processAction($p10L, 'hasil_lengkap', [], $jaksaA->id, 'USER');
            $p10B = $createPerkara('010B');
            $p10B->update(['tahap_saat_ini' => 'P-24', 'status_saat_ini' => 'Menunggu Penelitian Berkas']);
            PerkaraWorkflowService::processAction($p10B, 'hasil_belum_lengkap', [], $jaksaA->id, 'USER');
            $results[] = [
                'Test' => 'TEST 10 - P-24 BRANCHING',
                'Expected' => 'Lengkap -> P-21, Belum -> P-19',
                'Actual' => "Lengkap: {$p10L->fresh()->tahap_saat_ini}, Belum: {$p10B->fresh()->tahap_saat_ini}",
                'Status' => ($p10L->fresh()->tahap_saat_ini === 'P-21' && $p10B->fresh()->tahap_saat_ini === 'P-19') ? 'PASS' : 'FAIL'
            ];

            // TEST 11
            $p11 = $createPerkara('011');
            $p11->update(['tahap_saat_ini' => 'P-19', 'status_saat_ini' => 'Menunggu Pemenuhan Petunjuk']);
            PerkaraWorkflowService::createReminder($p11, 'P-19', 'SLA Pemenuhan Petunjuk', -5);
            $slaEngine->executeEngine();
            $tl11 = PerkaraTimeline::where('perkara_id', $p11->id)->orderBy('id', 'desc')->first();
            $results[] = [
                'Test' => 'TEST 11 - P-19 OVERDUE',
                'Expected' => 'Status: P-20 / Overdue, Actor: SYSTEM',
                'Actual' => "Status: {$p11->fresh()->status_saat_ini}, Actor: {$tl11->actor_type}",
                'Status' => ($p11->fresh()->status_saat_ini === 'P-20 / Overdue' && $tl11->actor_type === 'SYSTEM') ? 'PASS' : 'FAIL'
            ];

            // TEST 12
            $p12 = $createPerkara('012');
            $p12->update(['tahap_saat_ini' => 'P-21', 'status_saat_ini' => 'Menunggu Penyerahan Tahap II']);
            PerkaraWorkflowService::createReminder($p12, 'P-21', 'SLA Penyerahan Tahap II', -5);
            $slaEngine->executeEngine();
            $tl12 = PerkaraTimeline::where('perkara_id', $p12->id)->orderBy('id', 'desc')->first();
            $results[] = [
                'Test' => 'TEST 12 - P-21 OVERDUE',
                'Expected' => 'Status: FORM-7 / Overdue, Actor: SYSTEM',
                'Actual' => "Status: {$p12->fresh()->status_saat_ini}, Actor: {$tl12->actor_type}",
                'Status' => ($p12->fresh()->status_saat_ini === 'FORM-7 / Overdue' && $tl12->actor_type === 'SYSTEM') ? 'PASS' : 'FAIL'
            ];

            // TEST 13
            $p13 = $createPerkara('013');
            $p13->update(['tahap_saat_ini' => 'Monitoring Penyidikan', 'status_saat_ini' => 'P-17']);
            PerkaraWorkflowService::processAction($p13, 'catat_p17_2', [], $jaksaA->id, 'USER');
            $actionsP13 = array_column(PerkaraWorkflowService::getAvailableActions($p13->fresh()), 'id');
            PerkaraWorkflowService::createReminder($p13, 'Monitoring Penyidikan', 'FORM-2 Overdue test', -1);
            $slaEngine->executeEngine();
            $tl13 = PerkaraTimeline::where('perkara_id', $p13->id)->orderBy('id', 'desc')->first();
            $results[] = [
                'Test' => 'TEST 13 - P-17 KEDUA / FORM-2 BRANCHING / OVERDUE',
                'Expected' => 'FORM-3 Available action, auto trans',
                'Actual' => "Available action: " . implode(',', $actionsP13) . " | Result Status: {$p13->fresh()->status_saat_ini}",
                'Status' => (in_array('catat_form3', $actionsP13) && !in_array('catat_p17_2', $actionsP13) && $p13->fresh()->status_saat_ini === 'FORM-3') ? 'PASS' : 'FAIL'
            ];

            file_put_contents('test_final.json', json_encode($results, JSON_PRETTY_PRINT));
            $this->info("Done writing results!");

        } catch (Exception $e) {
            $this->error("Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }
}
