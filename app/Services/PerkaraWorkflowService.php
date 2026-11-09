<?php

namespace App\Services;

use App\Models\Perkara;
use App\Models\PerkaraTimeline;
use App\Models\PerkaraReminder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PerkaraWorkflowService
{
    // Tahap
    public const TAHAP_KOORDINASI = 'Koordinasi Awal';
    public const TAHAP_MONITORING = 'Monitoring Penyidikan';
    public const TAHAP_P24 = 'P-24';
    public const TAHAP_P19 = 'P-19';
    public const TAHAP_P21 = 'P-21';
    public const TAHAP_II = 'Tahap II';

    // Mendapatkan opsi aksi berdasarkan tahap dan status saat ini
    public static function getAvailableActions(Perkara $perkara): array
    {
        // Jika perkara pada tahap Koordinasi Awal telah overdue, semua aksi perkembangan dinonaktifkan / dikunci
        if ($perkara->isKoordinasiAwalOverdue()) {
            return [];
        }

        $tahap = $perkara->tahap_saat_ini;
        $status = $perkara->status_saat_ini;

        $actions = [];

        switch ($tahap) {
            case self::TAHAP_KOORDINASI:
                if (in_array($status, ['Menunggu Koordinasi', 'Koordinasi Selesai'])) {
                    $actions[] = ['id' => 'koordinasi_selesai', 'label' => 'Koordinasi Selesai'];
                }
                break;

            case self::TAHAP_MONITORING:
                // Pada tahap monitoring penyidikan, penerbitan P-17, SOP FORM-2, dan FORM-3 dijalankan secara otomatis oleh sistem (SLA Engine ketika melewati 30 hari).
                // Aksi manual yang tersedia untuk Jaksa hanya satu button: Konfirmasi Penerimaan Berkas (selama berkas belum FORM-3 / SPDP dikembalikan).
                if (!str_contains($status, 'FORM-3')) {
                    $actions[] = ['id' => 'terima_berkas', 'label' => 'Konfirmasi Penerimaan Berkas'];
                }
                break;

            case self::TAHAP_P24:
                if ($status === 'Menunggu Penelitian Berkas') {
                    $actions[] = ['id' => 'hasil_lengkap', 'label' => 'Berkas Lengkap (P-21)'];
                    $actions[] = ['id' => 'hasil_belum_lengkap', 'label' => 'Berkas Belum Lengkap (P-18/P-19)'];
                }
                break;

            case self::TAHAP_P19:
                // Penerbitan P-20 dijalankan secara otomatis oleh sistem (SLA Engine jika melewati batas waktu 14 hari).
                // Aksi manual yang tersedia untuk Jaksa hanya konfirmasi penerimaan berkas pemenuhan petunjuk.
                if (!str_contains($status, 'P-20')) {
                    $actions[] = ['id' => 'terima_pemenuhan_p19', 'label' => 'Terima Pemenuhan Petunjuk'];
                }
                break;

            case self::TAHAP_P21:
                // Penerbitan SOP FORM-7 dijalankan secara otomatis oleh sistem (SLA Engine jika melewati batas waktu 14 hari).
                // Aksi manual yang tersedia untuk Jaksa hanya konfirmasi penyerahan Tersangka & Barang Bukti (Tahap II).
                if (!str_contains($status, 'FORM-7')) {
                    $actions[] = ['id' => 'terima_tahap2', 'label' => 'Penyerahan Tahap II'];
                }
                break;

            case self::TAHAP_II:
                if ($status === 'Menunggu Pelaksanaan Tahap II') {
                    $actions[] = ['id' => 'tahap2_selesai', 'label' => 'Tahap II Selesai'];
                }
                break;
        }

        return $actions;
    }

    /**
     * Mendapatkan opsi aksi khusus untuk formulir 'Catat Perkembangan' di Website Admin.
     * Mengikuti alur bertahap:
     * - Menunggu Berkas Tahap I -> [Konfirmasi Penerimaan Berkas, Penerbitan P-17]
     * - P-17 -> [Konfirmasi Penerimaan Berkas, Penerbitan SOP FORM-2 / P-17 Kedua]
     * - SOP FORM-2 -> [Konfirmasi Penerimaan Berkas, SOP FORM-3 / Pengembalian SPDP]
     * - SOP FORM-3 -> Selesai / Arsip (tidak ada aksi lanjutan)
     */
    public static function getWebAvailableActions(Perkara $perkara): array
    {
        if ($perkara->isKoordinasiAwalOverdue()) {
            return [];
        }

        $tahap = $perkara->tahap_saat_ini;
        $status = $perkara->status_saat_ini;

        if ($tahap === self::TAHAP_MONITORING) {
            // Jika sudah FORM-3 / SPDP dikembalikan, berkas sudah selesai & diarsipkan
            if (str_contains($status, 'FORM-3')) {
                return [];
            }

            $actions = [];

            // Konfirmasi Penerimaan Berkas selalu ada selama berkas belum FORM-3
            $actions[] = ['id' => 'terima_berkas', 'label' => 'Konfirmasi Penerimaan Berkas'];

            // 1. Baru masuk / Menunggu Berkas Tahap I -> Hanya P-17 dan Konfirmasi Penerimaan Berkas
            if ($status === 'Menunggu Berkas Tahap I' || (!str_contains($status, 'P-17') && !str_contains($status, 'FORM-2'))) {
                $actions[] = ['id' => 'catat_p17', 'label' => 'Penerbitan P-17 (Permintaan Perkembangan Penyidikan)'];
            }
            // 2. Sudah P-17 -> Hanya SOP FORM-2 (P-17 Kedua) dan Konfirmasi Penerimaan Berkas
            elseif (str_contains($status, 'P-17') && !str_contains($status, 'Kedua') && !str_contains($status, 'FORM-2')) {
                $actions[] = ['id' => 'catat_p17_2', 'label' => 'Penerbitan SOP FORM-2 (P-17 Kedua)'];
            }
            // 3. Sudah SOP FORM-2 / P-17 Kedua -> Hanya SOP FORM-3 (Pengembalian SPDP) dan Konfirmasi Penerimaan Berkas
            elseif (str_contains($status, 'FORM-2') || str_contains($status, 'Kedua')) {
                $actions[] = ['id' => 'catat_form3', 'label' => 'Pengembalian SPDP & Simpan Arsip (SOP FORM-3)'];
            }

            return $actions;
        }

        // Untuk tahapan selain monitoring, gunakan aksi baku yang sudah ada
        return self::getAvailableActions($perkara);
    }

    public static function processAction(Perkara $perkara, string $actionId, array $data, ?int $userId = null, string $actorType = 'USER'): void
    {
        if ($perkara->isKoordinasiAwalOverdue() && $actionId !== 'tindak_lanjut_koordinasi_overdue') {
            throw new \DomainException('Perkara pada tahap Koordinasi Awal telah overdue dan terkunci dari pencatatan perkembangan.');
        }

        DB::transaction(function () use ($perkara, $actionId, $data, $userId, $actorType) {
            $catatan = $data['catatan'] ?? null;
            $tanggal = isset($data['tanggal']) ? Carbon::parse($data['tanggal']) : now();

            $prevTahap = $perkara->tahap_saat_ini;
            $prevStatus = $perkara->status_saat_ini;

            $newTahap = $perkara->tahap_saat_ini;
            $newStatus = $perkara->status_saat_ini;
            $eventName = '';

            switch ($actionId) {
                // Tahap KOORDINASI AWAL
                case 'tindak_lanjut_koordinasi_overdue':
                    $newStatus = 'Overdue / Tindak Lanjut SOP Form-1B & Form-1C';

                    // 1. Tindak Lanjut Laporan SOP Form-1B (idempotent check)
                    $hasForm1B = PerkaraTimeline::where('perkara_id', $perkara->id)
                        ->where('tahap', self::TAHAP_KOORDINASI)
                        ->where('status_kegiatan', 'like', '%Form-1B%')
                        ->exists();

                    if (!$hasForm1B) {
                        PerkaraTimeline::create([
                            'perkara_id' => $perkara->id,
                            'user_id' => $userId,
                            'actor_type' => $actorType,
                            'tahap' => self::TAHAP_KOORDINASI,
                            'status_kegiatan' => 'Penerbitan Laporan SOP Form-1B' . ($actorType === 'SYSTEM' ? ' (System Auto-Transition)' : ''),
                            'tanggal_kejadian' => $tanggal,
                            'catatan' => $catatan ?? 'Batas waktu SLA 3 hari Koordinasi Awal terlewati. Sistem secara otomatis mencatat penerbitan Laporan SOP Form-1B.',
                        ]);
                    }

                    // 2. Tindak Lanjut Teguran SOP Form-1C (idempotent check)
                    $hasForm1C = PerkaraTimeline::where('perkara_id', $perkara->id)
                        ->where('tahap', self::TAHAP_KOORDINASI)
                        ->where('status_kegiatan', 'like', '%Form-1C%')
                        ->exists();

                    if (!$hasForm1C) {
                        PerkaraTimeline::create([
                            'perkara_id' => $perkara->id,
                            'user_id' => $userId,
                            'actor_type' => $actorType,
                            'tahap' => self::TAHAP_KOORDINASI,
                            'status_kegiatan' => 'Penerbitan Teguran SOP Form-1C' . ($actorType === 'SYSTEM' ? ' (System Auto-Transition)' : ''),
                            'tanggal_kejadian' => $tanggal,
                            'catatan' => $catatan ?? 'Batas waktu SLA 3 hari Koordinasi Awal terlewati. Sistem secara otomatis mencatat penerbitan Teguran SOP Form-1C.',
                        ]);
                    }

                    // Nonaktifkan reminder aktif pada tahap Koordinasi Awal (riwayat tetap tersimpan)
                    self::resolveActiveReminders($perkara, self::TAHAP_KOORDINASI);

                    $eventName = ''; // Sudah dicatat spesifik per tindak lanjut Form-1B dan Form-1C di atas
                    break;

                case 'koordinasi_selesai':
                case 'lanjut_monitoring':
                    $newTahap = self::TAHAP_MONITORING;
                    $newStatus = 'Menunggu Berkas Tahap I';
                    $eventName = 'Koordinasi Selesai (Mulai Monitoring Penyidikan)';
                    self::resolveActiveReminders($perkara, self::TAHAP_KOORDINASI);

                    $hasTahap1Reminder = PerkaraReminder::where('perkara_id', $perkara->id)
                        ->where('tahap', self::TAHAP_MONITORING)
                        ->where(function ($q) {
                            $q->where('jenis_reminder', 'Batas Penyerahan Tahap I')
                                ->orWhere('jenis_reminder', 'like', '%Tahap I%');
                        })
                        ->where('is_active', true)
                        ->exists();

                    if (!$hasTahap1Reminder) {
                        // SLA Tahap I = 30 hari dari tanggal SPDP (atau tanggal input jika tidak ada)
                        $spdpDate = $perkara->tanggal_spdp ? Carbon::parse($perkara->tanggal_spdp) : ($perkara->created_at ?? now());
                        $deadline = $spdpDate->copy()->addDays(30);

                        PerkaraReminder::create([
                            'perkara_id' => $perkara->id,
                            'tahap' => self::TAHAP_MONITORING,
                            'jenis_reminder' => 'Batas Penyerahan Tahap I',
                            'tanggal_mulai' => now(),
                            'deadline' => $deadline,
                            'status_urgensi' => PerkaraReminder::determineUrgensi($deadline),
                            'is_active' => true,
                        ]);
                    }
                    break;

                // Tahap MONITORING
                case 'catat_p17':
                    $newStatus = 'P-17';
                    $eventName = 'Penerbitan P-17 (Permintaan Perkembangan Penyidikan)';
                    if ($actorType === 'SYSTEM') {
                        $eventName .= ' (System Auto-Transition)';
                        $catatan = $catatan ?? 'Batas waktu 30 hari penyerahan berkas Tahap I setelah SPDP terlewati tanpa penyerahan berkas. Sistem secara otomatis menerbitkan P-17.';
                    } else {
                        $catatan = $catatan ?? 'Penerbitan P-17 (Surat Permintaan Perkembangan Penyidikan) kepada Penyidik.';
                    }
                    self::resolveActiveReminders($perkara, self::TAHAP_MONITORING);

                    $hasActiveP17Reminder = PerkaraReminder::where('perkara_id', $perkara->id)
                        ->where('tahap', self::TAHAP_MONITORING)
                        ->where(function ($q) {
                            $q->where('jenis_reminder', 'like', '%P-17%')
                                ->orWhere('jenis_reminder', 'like', '%Batas Waktu P-17%');
                        })
                        ->where('is_active', true)
                        ->exists();

                    if (!$hasActiveP17Reminder) {
                        self::createReminder($perkara, self::TAHAP_MONITORING, 'Batas Waktu P-17 (30 Hari)', 30);
                    }

                    $hasP17Timeline = PerkaraTimeline::where('perkara_id', $perkara->id)
                        ->where('tahap', self::TAHAP_MONITORING)
                        ->where('status_kegiatan', 'like', '%P-17%')
                        ->where('status_kegiatan', 'not like', '%Kedua%')
                        ->where('status_kegiatan', 'not like', '%FORM-2%')
                        ->exists();

                    if ($hasP17Timeline && $actorType === 'SYSTEM') {
                        $eventName = '';
                    }
                    break;

                case 'catat_p17_2':
                    $newStatus = 'P-17 Kedua / FORM-2';
                    $eventName = 'Penerbitan SOP FORM-2 (P-17 Kedua)';
                    if ($actorType === 'SYSTEM') {
                        $eventName .= ' (System Auto-Transition)';
                        $catatan = $catatan ?? 'Batas waktu 30 hari setelah P-17 terlewati tanpa penyerahan berkas dari penyidik. Sistem secara otomatis menerbitkan SOP FORM-2 (P-17 Kedua).';
                    } else {
                        $catatan = $catatan ?? 'Penerbitan SOP FORM-2 (Surat Peringatan / P-17 Kedua) kepada Penyidik.';
                    }
                    self::resolveActiveReminders($perkara, self::TAHAP_MONITORING);

                    $hasActiveForm2Reminder = PerkaraReminder::where('perkara_id', $perkara->id)
                        ->where('tahap', self::TAHAP_MONITORING)
                        ->where(function ($q) {
                            $q->where('jenis_reminder', 'like', '%FORM-2%')
                                ->orWhere('jenis_reminder', 'like', '%Penataan setelah FORM-2%');
                        })
                        ->where('is_active', true)
                        ->exists();

                    if (!$hasActiveForm2Reminder) {
                        self::createReminder($perkara, self::TAHAP_MONITORING, 'Batas Waktu SOP FORM-2 (30 Hari)', 30);
                    }

                    $hasForm2Timeline = PerkaraTimeline::where('perkara_id', $perkara->id)
                        ->where('tahap', self::TAHAP_MONITORING)
                        ->where('status_kegiatan', 'like', '%FORM-2%')
                        ->exists();

                    if ($hasForm2Timeline && $actorType === 'SYSTEM') {
                        $eventName = '';
                    }
                    break;

                case 'catat_form3':
                    $newStatus = 'SOP FORM-3 (Pengembalian SPDP)';
                    $eventName = 'Pengembalian SPDP dan Simpan Arsip (SOP FORM-3)';
                    if ($actorType === 'SYSTEM') {
                        $eventName .= ' (System Auto-Transition)';
                        $catatan = $catatan ?? 'Batas waktu 30 hari setelah SOP FORM-2 terlewati tanpa penyerahan berkas. Sistem secara otomatis mencatatkan SOP FORM-3: Pengembalian SPDP kepada Penyidik dan Simpan Arsip (Perkara Selesai).';
                    } else {
                        $catatan = $catatan ?? 'Penerbitan SOP FORM-3: Berkas SPDP dikembalikan kepada Penyidik dan administrasi perkara disimpan ke arsip (Perkara Selesai).';
                    }
                    // Nonaktifkan semua reminder aktif pada perkara ini karena sudah selesai & SPDP dikembalikan
                    self::resolveActiveReminders($perkara, self::TAHAP_MONITORING);
                    PerkaraReminder::where('perkara_id', $perkara->id)
                        ->where('is_active', true)
                        ->update(['is_active' => false]);

                    $hasForm3Timeline = PerkaraTimeline::where('perkara_id', $perkara->id)
                        ->where('tahap', self::TAHAP_MONITORING)
                        ->where('status_kegiatan', 'like', '%FORM-3%')
                        ->exists();

                    if ($hasForm3Timeline && $actorType === 'SYSTEM') {
                        $eventName = '';
                    }
                    break;
                case 'terima_berkas':
                    $newTahap = self::TAHAP_P24;
                    $newStatus = 'Menunggu Penelitian Berkas';
                    $eventName = 'Penyerahan Berkas Tahap I Diterima';
                    self::resolveActiveReminders($perkara, self::TAHAP_MONITORING);
                    self::createReminder($perkara, self::TAHAP_P24, 'SLA Penelitian Berkas (P-24)', 7);
                    break;

                // Tahap P-24 (HASIL)
                case 'hasil_lengkap':
                    $newTahap = self::TAHAP_P21;
                    $newStatus = 'Menunggu Penyerahan Tahap II';
                    $eventName = 'Berkas Dinyatakan Lengkap (P-21)';
                    self::resolveActiveReminders($perkara, self::TAHAP_P24);
                    self::createReminder($perkara, self::TAHAP_P21, 'SLA Penyerahan Tahap II', 14);
                    break;
                case 'hasil_belum_lengkap':
                    $newTahap = self::TAHAP_P19;
                    $newStatus = 'Menunggu Pemenuhan Petunjuk';
                    $eventName = 'Berkas Belum Lengkap, Terbit P-18/P-19';
                    self::resolveActiveReminders($perkara, self::TAHAP_P24);
                    self::createReminder($perkara, self::TAHAP_P19, 'SLA Pemenuhan Petunjuk', 14);
                    break;

                // Tahap P-19
                case 'terima_pemenuhan_p19':
                    $newTahap = self::TAHAP_P24;
                    $newStatus = 'Menunggu Penelitian Berkas';
                    $eventName = 'Pemenuhan Petunjuk (P-19) Diterima';
                    self::resolveActiveReminders($perkara, self::TAHAP_P19);
                    self::createReminder($perkara, self::TAHAP_P24, 'SLA Penelitian Berkas (P-24)', 7);
                    break;
                case 'p20':
                    $newStatus = 'P-20 (Waktu Penyidikan Tambahan Habis / Pengembalian SPDP)';
                    $eventName = 'Penerbitan P-20 (Pengembalian SPDP)';
                    if ($actorType === 'SYSTEM') {
                        $eventName .= ' (System Auto-Transition)';
                        $catatan = $catatan ?? 'Batas waktu 14 hari pemenuhan petunjuk (P-19) terlewati tanpa penyerahan kembali berkas dari penyidik. Sistem secara otomatis mencatat penerbitan P-20 (Waktu Penyidikan Tambahan Habis / Pengembalian SPDP - Perkara Selesai).';
                    } else {
                        $catatan = $catatan ?? 'Penerbitan P-20: Pemberitahuan waktu penyidikan tambahan telah habis dan berkas SPDP dikembalikan ke Penyidik (Perkara Selesai).';
                    }
                    self::resolveActiveReminders($perkara, self::TAHAP_P19);
                    PerkaraReminder::where('perkara_id', $perkara->id)
                        ->where('is_active', true)
                        ->update(['is_active' => false]);

                    $hasP20Timeline = PerkaraTimeline::where('perkara_id', $perkara->id)
                        ->where('tahap', self::TAHAP_P19)
                        ->where('status_kegiatan', 'like', '%P-20%')
                        ->exists();

                    if ($hasP20Timeline && $actorType === 'SYSTEM') {
                        $eventName = '';
                    }
                    break;

                // Tahap P-21
                case 'terima_tahap2':
                    $newTahap = self::TAHAP_II;
                    $newStatus = 'Menunggu Pelaksanaan Tahap II';
                    $eventName = 'Penyerahan Tersangka & Barang Bukti (Tahap II)';
                    self::resolveActiveReminders($perkara, self::TAHAP_P21);
                    break;
                case 'form7':
                    $newStatus = 'SOP FORM-7 (Pengembalian Berkas & SPDP ke Penyidik)';
                    $eventName = 'Penerbitan SOP FORM-7 (Pengembalian Berkas & SPDP)';
                    if ($actorType === 'SYSTEM') {
                        $eventName .= ' (System Auto-Transition)';
                        $catatan = $catatan ?? 'Batas waktu 14 hari penyerahan tanggung jawab Tersangka dan Barang Bukti (Tahap II) terlewati. Sistem secara otomatis mencatat penerbitan SOP FORM-7 (Pengembalian berkas perkara & SPDP ke penyidik - Perkara Selesai).';
                    } else {
                        $catatan = $catatan ?? 'Penerbitan SOP FORM-7: Berkas perkara & SPDP dikembalikan ke Penyidik karena batas waktu Tahap II terlewati (Perkara Selesai).';
                    }
                    self::resolveActiveReminders($perkara, self::TAHAP_P21);
                    PerkaraReminder::where('perkara_id', $perkara->id)
                        ->where('is_active', true)
                        ->update(['is_active' => false]);

                    $hasForm7Timeline = PerkaraTimeline::where('perkara_id', $perkara->id)
                        ->where('tahap', self::TAHAP_P21)
                        ->where('status_kegiatan', 'like', '%FORM-7%')
                        ->exists();

                    if ($hasForm7Timeline && $actorType === 'SYSTEM') {
                        $eventName = '';
                    }
                    break;

                // Tahap II
                case 'tahap2_selesai':
                    $newStatus = 'Tahap II Selesai';
                    $eventName = 'Pelaksanaan Tahap II Selesai / Administrasi Penuntutan';
                    self::resolveActiveReminders($perkara, self::TAHAP_II);
                    break;
            }

            // Update Perkara
            $perkara->update([
                'tahap_saat_ini' => $newTahap,
                'status_saat_ini' => $newStatus,
            ]);

            // Save Timeline Record
            if (!empty($eventName)) {
                PerkaraTimeline::create([
                    'perkara_id' => $perkara->id,
                    'user_id' => $userId,
                    'actor_type' => $actorType,
                    'tahap' => $newTahap,
                    'tahap_sebelumnya' => $prevTahap,
                    'status_sebelumnya' => $prevStatus,
                    'status_kegiatan' => $eventName,
                    'tanggal_kejadian' => $tanggal,
                    'catatan' => $catatan,
                ]);

                try {
                    app(\App\Services\Notification\NotificationService::class)->notifyStageTransition($perkara, $eventName, $catatan, $userId);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('Gagal notif transisi: ' . $e->getMessage());
                }
            }
        });
    }

    /**
     * Mendapatkan timeline aksi perkembangan terakhir yang dapat dibatalkan (undo).
     * Syarat:
     * 1. Merupakan aksi perkembangan (bukan Pendaftaran Perkara awal).
     * 2. Belum pernah dibatalkan (is_reverted == false).
     * 3. Bukan merupakan baris catatan audit koreksi itu sendiri.
     */
    public static function canUndoLastAction(Perkara $perkara): ?PerkaraTimeline
    {
        return PerkaraTimeline::where('perkara_id', $perkara->id)
            ->where('is_reverted', false)
            ->where('status_kegiatan', 'not like', '%Pendaftaran Perkara%')
            ->where('status_kegiatan', 'not like', '%[KOREKSI%')
            ->latest('id')
            ->first();
    }

    /**
     * Membatalkan (undo) aksi perkembangan terakhir dengan pencatatan audit trail lengkap.
     */
    public static function undoLastAction(Perkara $perkara, string $reason, int $userId): PerkaraTimeline
    {
        return DB::transaction(function () use ($perkara, $reason, $userId) {
            $lastTimeline = self::canUndoLastAction($perkara);

            if (!$lastTimeline) {
                throw new \DomainException('Tidak ada langkah perkembangan perkara yang dapat dibatalkan.');
            }

            // Tentukan tahap & status target pengembalian
            if (!empty($lastTimeline->tahap_sebelumnya) && !empty($lastTimeline->status_sebelumnya)) {
                $targetTahap = $lastTimeline->tahap_sebelumnya;
                $targetStatus = $lastTimeline->status_sebelumnya;
            } else {
                [$targetTahap, $targetStatus] = self::deducePreviousState($lastTimeline);
            }

            // 1. Tandai timeline yang dibatalkan
            $lastTimeline->update([
                'is_reverted' => true,
                'reverted_at' => now(),
                'reverted_by' => $userId,
                'revert_reason' => $reason,
            ]);

            // 2. Nonaktifkan reminder aktif yang dihasilkan dari aksi keliru tersebut
            PerkaraReminder::where('perkara_id', $perkara->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // 3. Pulihkan status reminder tahap sebelumnya
            $previousReminder = PerkaraReminder::where('perkara_id', $perkara->id)
                ->where('tahap', $targetTahap)
                ->latest('id')
                ->first();

            if ($previousReminder && Carbon::parse($previousReminder->deadline)->isFuture()) {
                $previousReminder->update([
                    'is_active' => true,
                    'status_urgensi' => PerkaraReminder::determineUrgensi($previousReminder->deadline),
                ]);
            } else {
                self::restoreDefaultReminderForStage($perkara, $targetTahap, $targetStatus);
            }

            // 4. Perbarui status perkara kembali ke posisi sebelumnya
            $perkara->update([
                'tahap_saat_ini' => $targetTahap,
                'status_saat_ini' => $targetStatus,
            ]);

            // 5. Buat rekam jejak audit resmi di Timeline
            $correctionTimeline = PerkaraTimeline::create([
                'perkara_id' => $perkara->id,
                'user_id' => $userId,
                'actor_type' => 'USER',
                'tahap' => $targetTahap,
                'status_kegiatan' => '[KOREKSI ADMINISTRASI] Pembatalan Aksi: ' . $lastTimeline->status_kegiatan,
                'tanggal_kejadian' => now(),
                'catatan' => 'Alasan Koreksi: ' . $reason,
            ]);

            return $correctionTimeline;
        });
    }

    /**
     * Deduksi status sebelumnya untuk data lama yang belum memiliki kolom tahap_sebelumnya.
     */
    public static function deducePreviousState(PerkaraTimeline $timeline): array
    {
        $kegiatan = $timeline->status_kegiatan;

        if (str_contains($kegiatan, 'P-21') || str_contains($kegiatan, 'Lengkap')) {
            return [self::TAHAP_P24, 'Menunggu Penelitian Berkas'];
        }
        if (str_contains($kegiatan, 'P-18') || str_contains($kegiatan, 'P-19') || str_contains($kegiatan, 'Belum Lengkap')) {
            return [self::TAHAP_P24, 'Menunggu Penelitian Berkas'];
        }
        if (str_contains($kegiatan, 'Pemenuhan Petunjuk')) {
            return [self::TAHAP_P19, 'Menunggu Pemenuhan Petunjuk'];
        }
        if (str_contains($kegiatan, 'Penyerahan Berkas Tahap I')) {
            return [self::TAHAP_MONITORING, 'Menunggu Berkas Tahap I'];
        }
        if (str_contains($kegiatan, 'FORM-3')) {
            return [self::TAHAP_MONITORING, 'P-17 Kedua / FORM-2'];
        }
        if (str_contains($kegiatan, 'FORM-2') || str_contains($kegiatan, 'Kedua')) {
            return [self::TAHAP_MONITORING, 'P-17'];
        }
        if (str_contains($kegiatan, 'P-17')) {
            return [self::TAHAP_MONITORING, 'Menunggu Berkas Tahap I'];
        }
        if (str_contains($kegiatan, 'FORM-7')) {
            return [self::TAHAP_P21, 'Menunggu Penyerahan Tahap II'];
        }
        if (str_contains($kegiatan, 'Tahap II Selesai')) {
            return [self::TAHAP_II, 'Menunggu Pelaksanaan Tahap II'];
        }
        if (str_contains($kegiatan, 'Tahap II')) {
            return [self::TAHAP_P21, 'Menunggu Penyerahan Tahap II'];
        }
        if (str_contains($kegiatan, 'Koordinasi Selesai')) {
            return [self::TAHAP_KOORDINASI, 'Menunggu Koordinasi'];
        }

        return [self::TAHAP_KOORDINASI, 'Menunggu Koordinasi'];
    }

    /**
     * Memulihkan atau membuat ulang reminder baku sesuai tahapan yang dipulihkan.
     */
    public static function restoreDefaultReminderForStage(Perkara $perkara, string $tahap, string $status): void
    {
        switch ($tahap) {
            case self::TAHAP_KOORDINASI:
                self::createReminder($perkara, self::TAHAP_KOORDINASI, 'SLA Koordinasi Awal', 3);
                break;
            case self::TAHAP_MONITORING:
                if (str_contains($status, 'FORM-2') || str_contains($status, 'Kedua')) {
                    self::createReminder($perkara, self::TAHAP_MONITORING, 'Batas Waktu SOP FORM-2 (30 Hari)', 30);
                } elseif (str_contains($status, 'P-17')) {
                    self::createReminder($perkara, self::TAHAP_MONITORING, 'Batas Waktu P-17 (30 Hari)', 30);
                } elseif (!str_contains($status, 'FORM-3')) {
                    $spdpDate = $perkara->tanggal_spdp ? Carbon::parse($perkara->tanggal_spdp) : ($perkara->created_at ?? now());
                    $deadline = $spdpDate->copy()->addDays(30);
                    PerkaraReminder::create([
                        'perkara_id' => $perkara->id,
                        'tahap' => self::TAHAP_MONITORING,
                        'jenis_reminder' => 'Batas Penyerahan Tahap I',
                        'tanggal_mulai' => now(),
                        'deadline' => $deadline,
                        'status_urgensi' => PerkaraReminder::determineUrgensi($deadline),
                        'is_active' => true,
                    ]);
                }
                break;
            case self::TAHAP_P24:
                self::createReminder($perkara, self::TAHAP_P24, 'SLA Penelitian Berkas (P-24)', 7);
                break;
            case self::TAHAP_P19:
                self::createReminder($perkara, self::TAHAP_P19, 'SLA Pemenuhan Petunjuk', 14);
                break;
            case self::TAHAP_P21:
                self::createReminder($perkara, self::TAHAP_P21, 'SLA Penyerahan Tahap II', 14);
                break;
        }
    }

    public static function createReminder(Perkara $perkara, string $tahap, string $jenis, int $daysValid): void
    {
        $mulai = now();
        $deadline = now()->addDays($daysValid);

        PerkaraReminder::create([
            'perkara_id' => $perkara->id,
            'tahap' => $tahap,
            'jenis_reminder' => $jenis,
            'tanggal_mulai' => $mulai,
            'deadline' => $deadline,
            'status_urgensi' => PerkaraReminder::determineUrgensi($deadline),
            'is_active' => true,
        ]);
    }

    public static function resolveActiveReminders(Perkara $perkara, string $tahap): void
    {
        PerkaraReminder::where('perkara_id', $perkara->id)
            ->where('tahap', $tahap)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }
}
