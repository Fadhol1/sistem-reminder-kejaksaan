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
            $p4Fresh = $p4->fresh();
            $tlForm1B = PerkaraTimeline::where('perkara_id', $p4->id)->where('status_kegiatan', 'like', '%Form-1B%')->first();
            $tlForm1C = PerkaraTimeline::where('perkara_id', $p4->id)->where('status_kegiatan', 'like', '%Form-1C%')->first();

            $passP4 = ($p4Fresh->tahap_saat_ini === 'Koordinasi Awal')
                && ($r4->is_active === false)
                && ($tlForm1B && $tlForm1B->actor_type === 'SYSTEM' && $tlForm1B->user_id === null)
                && ($tlForm1C && $tlForm1C->actor_type === 'SYSTEM' && $tlForm1C->user_id === null);

            $results[] = [
                'Test' => 'TEST 4 - OVERDUE KOORDINASI AWAL (SOP FORM-1B & FORM-1C)',
                'Expected' => 'Tahap: Koordinasi Awal, Reminder: Closed, Form-1B & Form-1C generated by SYSTEM',
                'Actual' => "Tahap: {$p4Fresh->tahap_saat_ini}, Closed: " . ($r4->is_active ? 'No' : 'Yes') . ", Form-1B: " . ($tlForm1B ? 'OK' : 'Missing') . ", Form-1C: " . ($tlForm1C ? 'OK' : 'Missing'),
                'Status' => $passP4 ? 'PASS' : 'FAIL'
            ];

            // TEST 4B - IDEMPOTENCY KOORDINASI AWAL OVERDUE
            $tlCountP4Before = PerkaraTimeline::where('perkara_id', $p4->id)->count();
            $slaEngine->executeEngine();
            $tlCountP4After = PerkaraTimeline::where('perkara_id', $p4->id)->count();
            $results[] = [
                'Test' => 'TEST 4B - IDEMPOTENCY KOORDINASI AWAL',
                'Expected' => 'Timeline count unchanged after re-run',
                'Actual' => "TL: {$tlCountP4Before} -> {$tlCountP4After}",
                'Status' => ($tlCountP4Before === $tlCountP4After) ? 'PASS' : 'FAIL'
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

            // ==========================================
            // TEST WAJIB MONITORING PENYIDIKAN (1 s/d 8)
            // ==========================================

            // (1) Menunggu Berkas Tahap I overdue menghasilkan P-17 sekali
            $pM1 = $createPerkara('M1');
            $pM1->update(['tahap_saat_ini' => 'Monitoring Penyidikan', 'status_saat_ini' => 'Menunggu Berkas Tahap I']);
            PerkaraReminder::where('perkara_id', $pM1->id)->delete();
            PerkaraWorkflowService::createReminder($pM1, 'Monitoring Penyidikan', 'Batas Penyerahan Tahap I', -1);
            $slaEngine->executeEngine();
            $pM1->refresh();
            $rM1New = PerkaraReminder::where('perkara_id', $pM1->id)->active()->first();
            $tlM1P17 = PerkaraTimeline::where('perkara_id', $pM1->id)->where('status_kegiatan', 'like', '%Pengiriman P-17%')->count();
            $results[] = [
                'Test' => 'TEST WAJIB 1 - Menunggu Berkas Tahap I overdue menghasilkan P-17 sekali',
                'Expected' => 'Status: P-17, P-17 Timeline count: 1, New Reminder: Tindak lanjut P-17',
                'Actual' => "Status: {$pM1->status_saat_ini}, TL Count: {$tlM1P17}, Reminder: " . ($rM1New ? $rM1New->jenis_reminder : 'None'),
                'Status' => ($pM1->status_saat_ini === 'P-17' && $tlM1P17 === 1 && $rM1New && $rM1New->jenis_reminder === 'Tindak lanjut P-17') ? 'PASS' : 'FAIL'
            ];

            // (2) P-17 overdue menghasilkan FORM-2 sekali
            $pM2 = $createPerkara('M2');
            $pM2->update(['tahap_saat_ini' => 'Monitoring Penyidikan', 'status_saat_ini' => 'P-17']);
            PerkaraReminder::where('perkara_id', $pM2->id)->delete();
            PerkaraWorkflowService::createReminder($pM2, 'Monitoring Penyidikan', 'Tindak lanjut P-17', -1);
            $slaEngine->executeEngine();
            $pM2->refresh();
            $rM2New = PerkaraReminder::where('perkara_id', $pM2->id)->active()->first();
            $tlM2Form2 = PerkaraTimeline::where('perkara_id', $pM2->id)->where('status_kegiatan', 'like', '%FORM-2%')->count();
            $results[] = [
                'Test' => 'TEST WAJIB 2 - P-17 overdue menghasilkan FORM-2 sekali',
                'Expected' => 'Status: P-17 Kedua / FORM-2, Timeline count: 1, New Reminder: Penataan setelah FORM-2',
                'Actual' => "Status: {$pM2->status_saat_ini}, TL Count: {$tlM2Form2}, Reminder: " . ($rM2New ? $rM2New->jenis_reminder : 'None'),
                'Status' => ($pM2->status_saat_ini === 'P-17 Kedua / FORM-2' && $tlM2Form2 === 1 && $rM2New && $rM2New->jenis_reminder === 'Penataan setelah FORM-2') ? 'PASS' : 'FAIL'
            ];

            // (3) FORM-2 overdue menghasilkan FORM-3 dan status terminal
            $pM3 = $createPerkara('M3');
            $pM3->update(['tahap_saat_ini' => 'Monitoring Penyidikan', 'status_saat_ini' => 'P-17 Kedua / FORM-2']);
            PerkaraReminder::where('perkara_id', $pM3->id)->delete();
            PerkaraWorkflowService::createReminder($pM3, 'Monitoring Penyidikan', 'Penataan setelah FORM-2', -1);
            $slaEngine->executeEngine();
            $pM3->refresh();
            $rM3Active = PerkaraReminder::where('perkara_id', $pM3->id)->active()->count();
            $tlM3Form3 = PerkaraTimeline::where('perkara_id', $pM3->id)->where('status_kegiatan', 'like', '%FORM-3%')->count();
            $actionsM3 = PerkaraWorkflowService::getAvailableActions($pM3);
            $results[] = [
                'Test' => 'TEST WAJIB 3 - FORM-2 overdue menghasilkan FORM-3 dan status terminal',
                'Expected' => 'Status: FORM-3, Active Reminders: 0, Available Actions: 0',
                'Actual' => "Status: {$pM3->status_saat_ini}, Active Reminders: {$rM3Active}, Actions: " . count($actionsM3),
                'Status' => ($pM3->status_saat_ini === 'FORM-3' && $rM3Active === 0 && count($actionsM3) === 0 && $tlM3Form3 === 1) ? 'PASS' : 'FAIL'
            ];

            // (4) Menjalankan engine ulang tidak menduplikasi apa pun
            $tlCountM1Before = PerkaraTimeline::where('perkara_id', $pM1->id)->count();
            $tlCountM2Before = PerkaraTimeline::where('perkara_id', $pM2->id)->count();
            $tlCountM3Before = PerkaraTimeline::where('perkara_id', $pM3->id)->count();
            $slaEngine->executeEngine();
            $tlCountM1After = PerkaraTimeline::where('perkara_id', $pM1->id)->count();
            $tlCountM2After = PerkaraTimeline::where('perkara_id', $pM2->id)->count();
            $tlCountM3After = PerkaraTimeline::where('perkara_id', $pM3->id)->count();
            $passIdempotent = ($tlCountM1Before === $tlCountM1After) &&
                             ($tlCountM2Before === $tlCountM2After) &&
                             ($tlCountM3Before === $tlCountM3After);
            $results[] = [
                'Test' => 'TEST WAJIB 4 - Menjalankan engine ulang tidak menduplikasi apa pun',
                'Expected' => 'Timeline count M1, M2, M3 unchanged on re-run',
                'Actual' => "M1: {$tlCountM1Before}->{$tlCountM1After}, M2: {$tlCountM2Before}->{$tlCountM2After}, M3: {$tlCountM3Before}->{$tlCountM3After}",
                'Status' => $passIdempotent ? 'PASS' : 'FAIL'
            ];

            // (5) Berkas diterima sebelum deadline mencegah penerbitan otomatis yang tidak sesuai
            $pM5 = $createPerkara('M5');
            $pM5->update(['tahap_saat_ini' => 'Monitoring Penyidikan', 'status_saat_ini' => 'Menunggu Berkas Tahap I']);
            PerkaraReminder::where('perkara_id', $pM5->id)->delete();
            PerkaraWorkflowService::createReminder($pM5, 'Monitoring Penyidikan', 'Batas Penyerahan Tahap I', 10);
            PerkaraWorkflowService::processAction($pM5, 'terima_berkas', [], $jaksaA->id, 'USER');
            $pM5->refresh();
            $slaEngine->executeEngine();
            $pM5->refresh();
            $hasAnyMonitoringDoc = PerkaraTimeline::where('perkara_id', $pM5->id)
                ->where(function($q) {
                    $q->where('status_kegiatan', 'like', '%P-17%')
                      ->orWhere('status_kegiatan', 'like', '%FORM-2%')
                      ->orWhere('status_kegiatan', 'like', '%FORM-3%');
                })->exists();
            $results[] = [
                'Test' => 'TEST WAJIB 5 - Berkas diterima sebelum deadline mencegah penerbitan otomatis',
                'Expected' => 'Tahap: P-24, Status: Menunggu Penelitian Berkas, No Auto Monitoring Docs',
                'Actual' => "Tahap: {$pM5->tahap_saat_ini}, Status: {$pM5->status_saat_ini}, Has Monitoring Docs: " . ($hasAnyMonitoringDoc ? 'Yes' : 'No'),
                'Status' => ($pM5->tahap_saat_ini === 'P-24' && $pM5->status_saat_ini === 'Menunggu Penelitian Berkas' && !$hasAnyMonitoringDoc) ? 'PASS' : 'FAIL'
            ];

            // (6) Perkara terminal tidak menerima transisi monitoring otomatis berikutnya
            $statusM3Before = $pM3->fresh()->status_saat_ini;
            $slaEngine->executeEngine();
            $statusM3After = $pM3->fresh()->status_saat_ini;
            $rM3ActiveAfter = PerkaraReminder::where('perkara_id', $pM3->id)->active()->count();
            $results[] = [
                'Test' => 'TEST WAJIB 6 - Perkara terminal tidak menerima transisi monitoring berikutnya',
                'Expected' => 'Status tetap FORM-3, Active Reminders tetap 0',
                'Actual' => "Status: {$statusM3Before} -> {$statusM3After}, Active Reminders: {$rM3ActiveAfter}",
                'Status' => ($statusM3Before === 'FORM-3' && $statusM3After === 'FORM-3' && $rM3ActiveAfter === 0) ? 'PASS' : 'FAIL'
            ];

            // (7) Arsip dan timeline tetap ada
            $allTimelinesM3 = PerkaraTimeline::where('perkara_id', $pM3->id)->orderBy('id')->get();
            $hasInitialTimeline = $allTimelinesM3->contains(fn($tl) => str_contains($tl->status_kegiatan, 'Penerimaan SPDP') || str_contains($tl->status_kegiatan, 'Pendaftaran'));
            $hasTerminalTimeline = $allTimelinesM3->contains(fn($tl) => str_contains($tl->status_kegiatan, 'FORM-3'));
            $results[] = [
                'Test' => 'TEST WAJIB 7 - Arsip dan timeline tetap ada',
                'Expected' => 'Total TL >= 2, Initial TL exists, Terminal TL exists',
                'Actual' => "Total TL: {$allTimelinesM3->count()}, Has Initial: " . ($hasInitialTimeline ? 'Yes' : 'No') . ", Has Terminal: " . ($hasTerminalTimeline ? 'Yes' : 'No'),
                'Status' => ($allTimelinesM3->count() >= 2 && $hasInitialTimeline && $hasTerminalTimeline) ? 'PASS' : 'FAIL'
            ];

            // (8) Reminder lama dinonaktifkan, bukan dihapus
            $totalRemindersM3 = PerkaraReminder::where('perkara_id', $pM3->id)->count();
            $inactiveRemindersM3 = PerkaraReminder::where('perkara_id', $pM3->id)->where('is_active', false)->count();
            $results[] = [
                'Test' => 'TEST WAJIB 8 - Reminder lama dinonaktifkan, bukan dihapus',
                'Expected' => 'Total Reminders >= 1, Inactive Reminders === Total Reminders',
                'Actual' => "Total: {$totalRemindersM3}, Inactive: {$inactiveRemindersM3}",
                'Status' => ($totalRemindersM3 >= 1 && $totalRemindersM3 === $inactiveRemindersM3) ? 'PASS' : 'FAIL'
            ];

            // TEST 14 - KOORDINASI SELESAI LANGSUNG MONITORING PENYIDIKAN
            $pK = $createPerkara('K014');
            $actionsKBefore = array_column(PerkaraWorkflowService::getAvailableActions($pK), 'id');
            PerkaraWorkflowService::processAction($pK, 'koordinasi_selesai', ['catatan' => 'Koordinasi tuntas'], $jaksaA->id, 'USER');
            $pK->refresh();
            $rKOld = PerkaraReminder::where('perkara_id', $pK->id)->where('tahap', PerkaraWorkflowService::TAHAP_KOORDINASI)->first();
            $rKNew = PerkaraReminder::where('perkara_id', $pK->id)->where('tahap', PerkaraWorkflowService::TAHAP_MONITORING)->active()->first();
            $tlK = PerkaraTimeline::where('perkara_id', $pK->id)->where('status_kegiatan', 'like', '%Koordinasi Selesai%')->first();
            $actionsKAfter = array_column(PerkaraWorkflowService::getAvailableActions($pK), 'id');

            $passDirectMonitoring = (
                in_array('koordinasi_selesai', $actionsKBefore) &&
                !in_array('lanjut_monitoring', $actionsKBefore) &&
                $pK->tahap_saat_ini === PerkaraWorkflowService::TAHAP_MONITORING &&
                $pK->status_saat_ini === 'Menunggu Berkas Tahap I' &&
                $rKOld && !$rKOld->is_active &&
                $rKNew && $rKNew->jenis_reminder === 'Batas Penyerahan Tahap I' &&
                $tlK !== null &&
                !in_array('lanjut_monitoring', $actionsKAfter) &&
                in_array('catat_p17', $actionsKAfter)
            );

            $results[] = [
                'Test' => 'TEST 14 - KOORDINASI SELESAI LANGSUNG KE MONITORING PENYIDIKAN',
                'Expected' => 'Langsung Tahap: Monitoring Penyidikan, Status: Menunggu Berkas Tahap I, No lanjut_monitoring step',
                'Actual' => "Tahap: {$pK->tahap_saat_ini}, Status: {$pK->status_saat_ini}, Reminder Tahap I: " . ($rKNew ? $rKNew->jenis_reminder : 'None') . ", Actions After: " . implode(',', $actionsKAfter),
                'Status' => $passDirectMonitoring ? 'PASS' : 'FAIL'
            ];

            file_put_contents('test_final.json', json_encode($results, JSON_PRETTY_PRINT));
            $this->info("Done writing results!");

        } catch (Exception $e) {
            $this->error("Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        }
    }
}
