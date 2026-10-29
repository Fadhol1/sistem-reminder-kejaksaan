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
                if (!str_contains($status, 'P-20')) {
                    $actions[] = ['id' => 'terima_pemenuhan_p19', 'label' => 'Terima Pemenuhan Petunjuk'];
                    $actions[] = ['id' => 'p20', 'label' => 'Terbitkan P-20 (Pengembalian SPDP)'];
                }
                break;

            case self::TAHAP_P21:
                if (!str_contains($status, 'FORM-7')) {
                    $actions[] = ['id' => 'terima_tahap2', 'label' => 'Penyerahan Tahap II'];
                    $actions[] = ['id' => 'form7', 'label' => 'Terbitkan SOP FORM-7 (Pengembalian Berkas & SPDP)'];
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

    public static function processAction(Perkara $perkara, string $actionId, array $data, ?int $userId = null, string $actorType = 'USER'): void
    {
        if ($perkara->isKoordinasiAwalOverdue() && $actionId !== 'tindak_lanjut_koordinasi_overdue') {
            throw new \DomainException('Perkara pada tahap Koordinasi Awal telah overdue dan terkunci dari pencatatan perkembangan.');
        }

        DB::transaction(function () use ($perkara, $actionId, $data, $userId, $actorType) {
            $catatan = $data['catatan'] ?? null;
            $tanggal = isset($data['tanggal']) ? Carbon::parse($data['tanggal']) : now();

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
                    // Note for SLA Engine: P-24 has manual branch (lengkap/belum lengkap). System will NOT automate this.
                    $newStatus = 'Menunggu Penelitian Berkas';
                    $eventName = 'Penyerahan Berkas Tahap I Diterima';
                    self::resolveActiveReminders($perkara, self::TAHAP_MONITORING);
                    break;

                // Tahap P-24 (HASIL)
                case 'hasil_lengkap':
                    $newTahap = self::TAHAP_P21;
                    $newStatus = 'Menunggu Penyerahan Tahap II';
                    $eventName = 'Berkas Dinyatakan Lengkap (P-21)';
                    self::createReminder($perkara, self::TAHAP_P21, 'SLA Penyerahan Tahap II', 14);
                    break;
                case 'hasil_belum_lengkap':
                    $newTahap = self::TAHAP_P19;
                    $newStatus = 'Menunggu Pemenuhan Petunjuk';
                    $eventName = 'Berkas Belum Lengkap, Terbit P-18/P-19';
                    self::createReminder($perkara, self::TAHAP_P19, 'SLA Pemenuhan Petunjuk', 14);
                    break;

                // Tahap P-19
                case 'terima_pemenuhan_p19':
                    $newTahap = self::TAHAP_P24;
                    $newStatus = 'Menunggu Penelitian Berkas';
                    $eventName = 'Pemenuhan Petunjuk (P-19) Diterima';
                    self::resolveActiveReminders($perkara, self::TAHAP_P19);
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
