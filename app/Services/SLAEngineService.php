<?php

namespace App\Services;

use App\Models\Perkara;
use App\Models\PerkaraReminder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SLAEngineService
{
    /**
     * Memeriksa dan mengeksekusi SLA secara On-Demand dengan throttling
     * agar data otomatis terupdate saat diakses via Web maupun Mobile App tanpa harus menunggu cron.
     */
    public static function runThrottled(int $seconds = 15): void
    {
        $cacheKey = 'sla_engine_last_throttled_run';
        if (Cache::has($cacheKey)) {
            return;
        }
        Cache::put($cacheKey, now()->timestamp, $seconds);

        app(self::class)->executeEngine();
    }

    /**
     * Mengevaluasi semua deadline yang lewat (Overdue) dan menjalankan auto-transition
     * jika aturan bisnis terpenuhi. Proses ini bersifat Idempotent.
     */
    public function processOverdueTransitions(): void
    {
        $this->executeEngine();
    }

    /**
     * Memetakan konfigurasi "Konsekuensi Berjalan Otomatis" dari setiap Tahapan
     * @return string|null (null jika tidak ada auto-transition)
     */
    private function getAutoTransitionActionForTahap(string $tahap): ?string
    {
        switch ($tahap) {
            case PerkaraWorkflowService::TAHAP_KOORDINASI:
                return 'tindak_lanjut_koordinasi_overdue';

            case PerkaraWorkflowService::TAHAP_MONITORING:
                return 'RESOLVE_BY_STATUS';

            case PerkaraWorkflowService::TAHAP_P19:
                return 'p20';

            case PerkaraWorkflowService::TAHAP_P21:
                return 'form7';
                
            case PerkaraWorkflowService::TAHAP_P24:
                return null;
        }

        return null;
    }

    /**
     * Bantuan untuk meresolve tahapan yang punya banyak status rentan (seperti Monitoring)
     * ke action ID yang akurat. 
     */
    private function resolveTransitionByStatus(string $tahap, string $status, ?PerkaraReminder $reminder = null): ?string
    {
        if ($tahap === PerkaraWorkflowService::TAHAP_KOORDINASI) {
            if ($status === 'Menunggu Koordinasi') {
                return 'tindak_lanjut_koordinasi_overdue';
            }
            return null;
        }

        if ($tahap === PerkaraWorkflowService::TAHAP_MONITORING) {
            $jenisReminder = $reminder ? $reminder->jenis_reminder : '';

            // 1. Jika menunggu berkas Tahap I (SLA 30 hari SPDP lewat) -> Otomatis Terbitkan P-17
            if ($status === 'Menunggu Berkas Tahap I' || str_contains($jenisReminder, 'Tahap I')) {
                return 'catat_p17';
            }

            // 2. Jika sudah P-17 (SLA 30 hari P-17 lewat tanpa berkas) -> Otomatis Terbitkan SOP FORM-2
            if ($status === 'P-17' || 
                (str_contains($status, 'P-17') && !str_contains($status, 'Kedua') && !str_contains($status, 'FORM-2')) || 
                (str_contains($jenisReminder, 'P-17') && !str_contains($jenisReminder, 'Kedua') && !str_contains($jenisReminder, 'FORM-2'))) {
                return 'catat_p17_2';
            }

            // 3. Jika sudah SOP FORM-2 (SLA 30 hari FORM-2 lewat tanpa berkas) -> Otomatis Terbitkan SOP FORM-3 (Pengembalian SPDP & Simpan Arsip)
            if (str_contains($status, 'FORM-2') || str_contains($jenisReminder, 'FORM-2')) {
                return 'catat_form3';
            }

            return null;
        }

        if ($tahap === PerkaraWorkflowService::TAHAP_P19) {
            if (!str_contains($status, 'P-20')) {
                return 'p20';
            }
            return null;
        }

        if ($tahap === PerkaraWorkflowService::TAHAP_P21) {
            if (!str_contains($status, 'FORM-7')) {
                return 'form7';
            }
            return null;
        }

        return null;
    }

    /**
     * Mengevaluasi kondisi SLA: SPDP > 30 hari pada Monitoring, dan semua reminder aktif yang Overdue.
     */
    public function executeEngine(): void
    {
        Log::info('SLAEngineService: Memulai pemeriksaan SLA berkala.');

        $processedCount = 0;

        // 1. Evaluasi khusus Monitoring Penyidikan: SPDP > 30 hari tanpa berkas Tahap I -> Otomatis terbitkan P-17
        $monitoringPerkaras = Perkara::where('tahap_saat_ini', PerkaraWorkflowService::TAHAP_MONITORING)
            ->where('status_saat_ini', 'Menunggu Berkas Tahap I')
            ->whereNotNull('tanggal_spdp')
            ->get();

        foreach ($monitoringPerkaras as $pSPDP) {
            $spdpDate = Carbon::parse($pSPDP->tanggal_spdp);
            if ($spdpDate->addDays(30)->isPast()) {
                try {
                    Log::info("SLAEngineService: SPDP > 30 hari terdeteksi pada Perkara ID {$pSPDP->id}. Menjalankan auto-transition P-17.");
                    PerkaraWorkflowService::processAction($pSPDP, 'catat_p17', [], null, 'SYSTEM');
                    $processedCount++;

                    try {
                        app(\App\Services\Notification\NotificationService::class)->notifyAutoTransition($pSPDP, 'catat_p17');
                    } catch (\Throwable $ne) {
                        Log::error("SLAEngineService: Gagal kirim notifikasi auto-transition P-17: " . $ne->getMessage());
                    }
                } catch (\Exception $e) {
                    Log::error("SLAEngineService: Gagal memproses auto-transition catat_p17 Perkara ID {$pSPDP->id}: " . $e->getMessage());
                }
            }
        }

        // 2. Evaluasi semua reminder aktif yang melewati batas waktu (Overdue)
        $activeReminders = PerkaraReminder::with('perkara')->active()->get();

        foreach ($activeReminders as $reminder) {
            $calculatedUrgensi = PerkaraReminder::determineUrgensi($reminder->deadline);
            if ($reminder->status_urgensi !== $calculatedUrgensi) {
                $reminder->status_urgensi = $calculatedUrgensi;
                $reminder->save();
            }

            // Integrasi Notifikasi SLA: Kirim push notification untuk urgensi H-3, Jatuh Tempo (H-0), dan Overdue
            if (in_array($calculatedUrgensi, ['H-3', 'Jatuh Tempo', 'Overdue'])) {
                try {
                    app(\App\Services\Notification\NotificationService::class)->notifyReminderUrgency($reminder, $calculatedUrgensi);
                } catch (\Throwable $ne) {
                    Log::error("SLAEngineService: Gagal kirim notifikasi reminder ID {$reminder->id}: " . $ne->getMessage());
                }
            }

            if ($reminder->status_urgensi !== 'Overdue') {
                continue;
            }

            $perkara = $reminder->perkara;
            if (!$perkara) {
                continue;
            }

            // Anomali: Reminder aktif tapi tahap sudah berubah. Tutup reminder ini agar data sinkron.
            if ($perkara->tahap_saat_ini !== $reminder->tahap) {
                $reminder->update(['is_active' => false]);
                continue;
            }

            // Jika perkara sudah dalam status selesai (FORM-3, P-20, FORM-7, atau Tahap II Selesai), tutup reminder
            if (str_contains($perkara->status_saat_ini, 'FORM-3') ||
                str_contains($perkara->status_saat_ini, 'P-20') ||
                str_contains($perkara->status_saat_ini, 'FORM-7') ||
                $perkara->status_saat_ini === 'Tahap II Selesai') {
                $reminder->update(['is_active' => false]);
                continue;
            }

            // Dapatkan action dari konjungsi tahap, status, dan reminder saat ini
            $transitionAction = $this->resolveTransitionByStatus($perkara->tahap_saat_ini, $perkara->status_saat_ini, $reminder);

            if ($transitionAction === 'NEEDS_CONFIRMATION') {
                Log::info("SLAEngineService: Overdue tahap {$perkara->tahap_saat_ini} (ID: {$perkara->id}) masih NEEDS_CONFIRMATION.");
                continue;
            }

            if ($transitionAction) {
                try {
                    Log::info("SLAEngineService: Transisi '{$transitionAction}' dijalankan untuk Perkara ID: {$perkara->id}");
                    PerkaraWorkflowService::processAction($perkara, $transitionAction, [], null, 'SYSTEM');
                    $processedCount++;

                    try {
                        app(\App\Services\Notification\NotificationService::class)->notifyAutoTransition($perkara, $transitionAction);
                    } catch (\Throwable $ne) {
                        Log::error("SLAEngineService: Gagal kirim notifikasi auto-transition '{$transitionAction}': " . $ne->getMessage());
                    }
                } catch (\Exception $e) {
                    Log::error("SLAEngineService: Gagal memproses auto-transition '{$transitionAction}' Perkara ID {$perkara->id}: " . $e->getMessage());
                }
            }
        }

        Log::info("SLAEngineService: Selesai. Transisi dieksekusi: {$processedCount}");
    }
}

