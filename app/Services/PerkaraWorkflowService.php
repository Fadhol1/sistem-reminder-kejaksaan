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
        $tahap = $perkara->tahap_saat_ini;
        $status = $perkara->status_saat_ini;

        $actions = [];

        switch ($tahap) {
            case self::TAHAP_KOORDINASI:
                if (in_array($status, ['Menunggu Koordinasi', 'Overdue / Tindak Lanjut SOP Form-1B & Form-1C', 'Koordinasi Selesai'])) {
                    $actions[] = ['id' => 'koordinasi_selesai', 'label' => 'Koordinasi Selesai'];
                }
                break;

            case self::TAHAP_MONITORING:
                if ($status === 'Menunggu Berkas Tahap I') {
                    $actions[] = ['id' => 'catat_p17', 'label' => 'Kirim P-17'];
                } elseif ($status === 'P-17') {
                    $actions[] = ['id' => 'catat_p17_2', 'label' => 'Kirim P-17 Kedua / FORM-2'];
                } elseif ($status === 'P-17 Kedua / FORM-2') {
                    $actions[] = ['id' => 'catat_form3', 'label' => 'Kirim FORM-3'];
                }

                // Terlepas dari status P-17 mana pun (selama belum FORM-3 / SPDP dikembalikan), user bisa mencatatkan penerimaan berkas Tahap I
                if ($status !== 'FORM-3') {
                    $actions[] = ['id' => 'terima_berkas', 'label' => 'Penyerahan Berkas Tahap I (P-24)'];
                }
                break;

            case self::TAHAP_P24:
                if ($status === 'Menunggu Penelitian Berkas') {
                    $actions[] = ['id' => 'hasil_lengkap', 'label' => 'Berkas Lengkap (P-21)'];
                    $actions[] = ['id' => 'hasil_belum_lengkap', 'label' => 'Berkas Belum Lengkap (P-18/P-19)'];
                }
                break;

            case self::TAHAP_P19:
                $actions[] = ['id' => 'terima_pemenuhan_p19', 'label' => 'Terima Pemenuhan Petunjuk'];
                if ($status !== 'P-20 / Overdue') {
                    $actions[] = ['id' => 'p20', 'label' => 'P-20 (Overdue)'];
                }
                break;

            case self::TAHAP_P21:
                $actions[] = ['id' => 'terima_tahap2', 'label' => 'Penyerahan Tahap II'];
                if ($status !== 'FORM-7 / Overdue') {
                    $actions[] = ['id' => 'form7', 'label' => 'FORM-7 (Overdue)'];
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
                        ->where('jenis_reminder', 'Batas Penyerahan Tahap I')
                        ->where('is_active', true)
                        ->exists();

                    if (!$hasTahap1Reminder) {
                        // SLA Tahap I = 30 hari dari tanggal perkara di-input / ditambah ke sistem (created_at)
                        $inputDate = $perkara->created_at ?? now();
                        $diffFromInput = Carbon::parse($inputDate)->diffInDays(now());
                        $sisaHari = max(0, 30 - $diffFromInput);
                        self::createReminder($perkara, self::TAHAP_MONITORING, 'Batas Penyerahan Tahap I', $sisaHari);
                    }
                    break;

                // Tahap MONITORING
                case 'catat_p17':
                    $newStatus = 'P-17';
                    $eventName = 'Pengiriman P-17';
                    if ($actorType === 'SYSTEM') {
                        $eventName .= ' (System Auto-Transition)';
                        $catatan = $catatan ?? 'Tenggat waktu 30 hari penyerahan berkas Tahap I terlewati. Sistem secara otomatis menerbitkan P-17.';
                    }
                    self::resolveActiveReminders($perkara, self::TAHAP_MONITORING);

                    $hasActiveP17Reminder = PerkaraReminder::where('perkara_id', $perkara->id)
                        ->where('tahap', self::TAHAP_MONITORING)
                        ->where('jenis_reminder', 'Tindak lanjut P-17')
                        ->where('is_active', true)
                        ->exists();
                    if (!$hasActiveP17Reminder) {
                        self::createReminder($perkara, self::TAHAP_MONITORING, 'Tindak lanjut P-17', 30);
                    }

                    $hasP17Timeline = PerkaraTimeline::where('perkara_id', $perkara->id)
                        ->where('tahap', self::TAHAP_MONITORING)
                        ->where('status_kegiatan', 'like', '%Pengiriman P-17%')
                        ->where('status_kegiatan', 'not like', '%Kedua%')
                        ->exists();
                    if ($hasP17Timeline) {
                        $eventName = '';
                    }
                    break;
                case 'catat_p17_2':
                    $newStatus = 'P-17 Kedua / FORM-2';
                    $eventName = 'Pengiriman P-17 Kedua / FORM-2';
                    if ($actorType === 'SYSTEM') {
                        $eventName .= ' (System Auto-Transition)';
                        $catatan = $catatan ?? 'Tenggat waktu P-17 terlewati. Sistem secara otomatis menaikkan status menjadi P-17 Kedua / FORM-2.';
                    }
                    self::resolveActiveReminders($perkara, self::TAHAP_MONITORING);

                    $hasActiveForm2Reminder = PerkaraReminder::where('perkara_id', $perkara->id)
                        ->where('tahap', self::TAHAP_MONITORING)
                        ->where('jenis_reminder', 'Penataan setelah FORM-2')
                        ->where('is_active', true)
                        ->exists();
                    if (!$hasActiveForm2Reminder) {
                        self::createReminder($perkara, self::TAHAP_MONITORING, 'Penataan setelah FORM-2', 30);
                    }

                    $hasForm2Timeline = PerkaraTimeline::where('perkara_id', $perkara->id)
                        ->where('tahap', self::TAHAP_MONITORING)
                        ->where('status_kegiatan', 'like', '%FORM-2%')
                        ->exists();
                    if ($hasForm2Timeline) {
                        $eventName = '';
                    }
                    break;
                case 'catat_form3':
                    $newStatus = 'FORM-3';
                    $eventName = 'Pengiriman FORM-3';
                    if ($actorType === 'SYSTEM') {
                        $eventName .= ' (System Auto-Transition)';
                        $catatan = $catatan ?? 'Tenggat waktu FORM-2 terlewati. Sistem secara otomatis mencatatkan SOP FORM-3 pengembalian SPDP (Alur Monitoring Selesai/Terminal).';
                    }
                    self::resolveActiveReminders($perkara, self::TAHAP_MONITORING);

                    $hasForm3Timeline = PerkaraTimeline::where('perkara_id', $perkara->id)
                        ->where('tahap', self::TAHAP_MONITORING)
                        ->where('status_kegiatan', 'like', '%FORM-3%')
                        ->exists();
                    if ($hasForm3Timeline) {
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
                    $newStatus = 'P-20 / Overdue';
                    $eventName = 'Penerbitan P-20';
                    if ($actorType === 'SYSTEM') {
                        $eventName .= ' (System Auto-Transition)';
                        $catatan = $catatan ?? 'Batas waktu 14 hari pemenuhan petunjuk (P-19) terlewati. Sistem menerbitkan P-20 secara otomatis.';
                    }
                    self::resolveActiveReminders($perkara, self::TAHAP_P19);
                    break;

                // Tahap P-21
                case 'terima_tahap2':
                    $newTahap = self::TAHAP_II;
                    $newStatus = 'Menunggu Pelaksanaan Tahap II';
                    $eventName = 'Penyerahan Tersangka & Barang Bukti (Tahap II)';
                    self::resolveActiveReminders($perkara, self::TAHAP_P21);
                    break;
                case 'form7':
                    $newStatus = 'FORM-7 / Overdue';
                    $eventName = 'Penerbitan FORM-7';
                    if ($actorType === 'SYSTEM') {
                        $eventName .= ' (System Auto-Transition)';
                        $catatan = $catatan ?? 'Batas waktu 14 hari Penyerahan Tahap II terlewati. Sistem menerbitkan FORM-7 secara otomatis.';
                    }
                    self::resolveActiveReminders($perkara, self::TAHAP_P21);
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
            'status_urgensi' => 'Aman',
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
