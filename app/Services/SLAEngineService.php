<?php

namespace App\Services;

use App\Models\PerkaraReminder;
use Illuminate\Support\Facades\Log;

class SLAEngineService
{
    /**
     * Mengevaluasi semua deadline yang lewat (Overdue) dan menjalankan auto-transition
     * jika aturan bisnis terpenuhi. Proses ini bersifat Idempotent.
     */
    public function processOverdueTransitions(): void
    {
        Log::info('SLAEngineService: Memulai pemeriksaan deadline (SLA).');

        // Ambil semua reminder aktif
        $activeReminders = PerkaraReminder::with('perkara')->active()->get();
        $processedCount = 0;

        foreach ($activeReminders as $reminder) {
            // Evaluasi status urgensi mutakhir tanpa harus save (hemat query) kecuali jika berubah
            $calculatedUrgensi = PerkaraReminder::determineUrgensi($reminder->deadline);
            if ($reminder->status_urgensi !== $calculatedUrgensi) {
                $reminder->status_urgensi = $calculatedUrgensi;
                $reminder->save();
            }

            // Jika status belum jatuh tempo kelewat (Overdue), lewati.
            if ($reminder->status_urgensi !== 'Overdue') {
                continue;
            }

            // Pastikan perkara tidak null dan state perkara sama persis dengan yang direminder
            $perkara = $reminder->perkara;
            if (!$perkara) {
                continue;
            }

            // Idempotency check: pastikan tahap saat ini dari perkara sama dengan reminder yang aktif
            if ($perkara->tahap_saat_ini !== $reminder->tahap) {
                // Anomali: Reminder aktif tapi tahap sudah berubah. Tutup reminder ini agar data sinkron.
                $reminder->update(['is_active' => false]);
                continue;
            }

            // Temukan transisi otomatis berdasarkan SLA yang bocor
            $transitionAction = $this->getAutoTransitionActionForTahap($perkara->tahap_saat_ini);

            if ($transitionAction === 'NEEDS_CONFIRMATION') {
                // Rule konkrit belum dikonfirmasi oleh analis bisnis/user. Lewati.
                Log::info("SLAEngineService: Overdue pada tahap {$perkara->tahap_saat_ini} (Perkara ID: {$perkara->id}) membutuhkan konfirmasi rule. Tidak ada transisi.");
                continue;
            }

            if ($transitionAction) {
                try {
                    // Panggil Workflow Service untuk mengubah status
                    // Note: argument user_id = null, actorType = 'SYSTEM'
                    Log::info("SLAEngineService: Mengeksekusi transisi '{$transitionAction}' untuk Perkara ID: {$perkara->id}");
                    PerkaraWorkflowService::processAction($perkara, $transitionAction, [], null, 'SYSTEM');
                    $processedCount++;
                } catch (\Exception $e) {
                    Log::error("SLAEngineService: Gagal memproses auto-transition '{$transitionAction}' untuk Perkara ID: {$perkara->id}. Error: " . $e->getMessage());
                }
            }
        }

        Log::info("SLAEngineService: Selesai. Total transisi SLA otomatis yang dieksekusi: {$processedCount}");
    }

    /**
     * Memetakan konfigurasi "Konsekuensi Berjalan Otomatis" dari setiap Tahapan
     * @return string|null (null jika tidak ada auto-transition)
     */
    private function getAutoTransitionActionForTahap(string $tahap): ?string
    {
        switch ($tahap) {
            case PerkaraWorkflowService::TAHAP_KOORDINASI:
                // Workflow 3 hari. Konsekuensi belum fix.
                return 'NEEDS_CONFIRMATION';

            case PerkaraWorkflowService::TAHAP_MONITORING:
                // Butuh detil jika ini status P-17 atau P-17 Kedua. 
                // Karena kita hanya pegang TAHAP_MONITORING, reminder ini harusnya menyimpan 
                // specific "jenis_reminder" untuk membedakannya. 
                // Kita kembalikan flag khusus agar di-resolve lebih teliti di method lain, 
                // atau diubah pendekatannya.
                
                // Mari ambil logic detailnya lewat method bantuan.
                return 'RESOLVE_BY_STATUS';

            case PerkaraWorkflowService::TAHAP_P19:
                return 'p20'; // Menjadi P-20

            case PerkaraWorkflowService::TAHAP_P21:
                return 'form7'; // Menjadi FORM-7
                
            case PerkaraWorkflowService::TAHAP_P24:
                // Mengikuti instruksi point 5: P-24 manual branching (Lengkap vs Belum).
                // Maka tidak ada auto transition oleh Engine.
                return null;
        }

        return null;
    }

    /**
     * Bantuan untuk meresolve tahapan yang punya banyak status rentan (seperti Monitoring)
     * ke action ID yang akurat. 
     * Method pengganti logika getAutoTransitionActionForTahap khusus monitoring.
     */
    private function resolveTransitionByStatus(string $tahap, string $status): ?string
    {
        if ($tahap === PerkaraWorkflowService::TAHAP_KOORDINASI) {
            return 'NEEDS_CONFIRMATION';
        }

        if ($tahap === PerkaraWorkflowService::TAHAP_MONITORING) {
            if ($status === 'P-17') {
                return 'catat_p17_2';
            }
            if ($status === 'P-17 Kedua / FORM-2') {
                return 'catat_form3';
            }
        }

        if ($tahap === PerkaraWorkflowService::TAHAP_P19) {
            return 'p20';
        }

        if ($tahap === PerkaraWorkflowService::TAHAP_P21) {
            return 'form7';
        }

        return null; // Tidak ada rules tembakan paksa
    }

    /**
     * Memperbarui logika proses: mengevaluasi berdasarkan Tahap dan Status.
     */
    public function executeEngine(): void
    {
        Log::info('SLAEngineService: Memulai pemeriksaan SLA berkala.');

        $activeReminders = PerkaraReminder::with('perkara')->active()->get();
        $processedCount = 0;

        foreach ($activeReminders as $reminder) {
            $calculatedUrgensi = PerkaraReminder::determineUrgensi($reminder->deadline);
            if ($reminder->status_urgensi !== $calculatedUrgensi) {
                $reminder->status_urgensi = $calculatedUrgensi;
                $reminder->save();
            }

            if ($reminder->status_urgensi !== 'Overdue') {
                continue;
            }

            $perkara = $reminder->perkara;
            if (!$perkara || $perkara->tahap_saat_ini !== $reminder->tahap) {
                if ($perkara && $perkara->tahap_saat_ini !== $reminder->tahap) {
                    $reminder->update(['is_active' => false]);
                }
                continue;
            }

            // Dapatkan action dari konjungsi tahap dan status saat ini
            $transitionAction = $this->resolveTransitionByStatus($perkara->tahap_saat_ini, $perkara->status_saat_ini);

            if ($transitionAction === 'NEEDS_CONFIRMATION') {
                Log::info("SLAEngineService: Overdue tahap {$perkara->tahap_saat_ini} (ID: {$perkara->id}) masih NEEDS_CONFIRMATION.");
                continue;
            }

            if ($transitionAction) {
                try {
                    Log::info("SLAEngineService: Transisi '{$transitionAction}' dijalankan untuk Perkara ID: {$perkara->id}");
                    PerkaraWorkflowService::processAction($perkara, $transitionAction, [], null, 'SYSTEM');
                    $processedCount++;
                } catch (\Exception $e) {
                    Log::error("SLAEngineService: Gagal memproses auto-transition '{$transitionAction}' Perkara ID {$perkara->id}: " . $e->getMessage());
                }
            }
        }

        Log::info("SLAEngineService: Selesai. Transisi dieksekusi: {$processedCount}");
    }
}
