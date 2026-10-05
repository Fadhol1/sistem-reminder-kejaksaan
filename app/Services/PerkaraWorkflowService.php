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
                if ($status === 'Menunggu Koordinasi') {
                    $actions[] = ['id' => 'koordinasi_selesai', 'label' => 'Koordinasi Selesai'];
                } elseif ($status === 'Koordinasi Selesai') {
                    $actions[] = ['id' => 'lanjut_monitoring', 'label' => 'Lanjut Monitoring Penyidikan'];
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
                
                // Terlepas dari status P-17 mana pun (selama belum pindah tahap), user bisa mencatatkan penerimaan berkas Tahap I
                $actions[] = ['id' => 'terima_berkas', 'label' => 'Penyerahan Berkas Tahap I (P-24)'];
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
                case 'koordinasi_selesai':
                    $newStatus = 'Koordinasi Selesai';
                    $eventName = 'Koordinasi Awal Selesai';
                    self::resolveActiveReminders($perkara, self::TAHAP_KOORDINASI);
                    break;
                case 'lanjut_monitoring':
                    $newTahap = self::TAHAP_MONITORING;
                    $newStatus = 'Menunggu Berkas Tahap I';
                    $eventName = 'Mulai Monitoring Penyidikan';
                    // SLA Tahap I = 30 hari dari tanggal SPDP
                    $diffFromSpdp = Carbon::parse($perkara->tanggal_spdp)->diffInDays(now());
                    $sisaHari = max(0, 30 - $diffFromSpdp);
                    self::createReminder($perkara, self::TAHAP_MONITORING, 'Batas Penyerahan Tahap I', $sisaHari);
                    break;

                // Tahap MONITORING
                case 'catat_p17':
                    $newStatus = 'P-17';
                    $eventName = 'Pengiriman P-17';
                    self::resolveActiveReminders($perkara, self::TAHAP_MONITORING);
                    self::createReminder($perkara, self::TAHAP_MONITORING, 'Tindak lanjut P-17', 30);
                    break;
                case 'catat_p17_2':
                    $newStatus = 'P-17 Kedua / FORM-2';
                    $eventName = 'Pengiriman P-17 Kedua / FORM-2';
                    if ($actorType === 'SYSTEM') {
                        $eventName .= ' (System Auto-Transition)';
                        $catatan = $catatan ?? 'Tenggat waktu P-17 terlewati. Sistem secara otomatis menaikkan status menjadi P-17 Kedua / FORM-2.';
                    }
                    self::resolveActiveReminders($perkara, self::TAHAP_MONITORING);
                    self::createReminder($perkara, self::TAHAP_MONITORING, 'Penataan setelah FORM-2', 30); // Note: Assume 30 days or NEEDS_CONFIRMATION next SLA? Given rules, we just set 30 for now until confirmed.
                    break;
                case 'catat_form3':
                    $newStatus = 'FORM-3';
                    $eventName = 'Pengiriman FORM-3';
                    if ($actorType === 'SYSTEM') {
                        $eventName .= ' (System Auto-Transition)';
                        $catatan = $catatan ?? 'Tenggat waktu FORM-2 terlewati. Sistem secara otomatis mencatatkan FORM-3.';
                    }
                    self::resolveActiveReminders($perkara, self::TAHAP_MONITORING);
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
            PerkaraTimeline::create([
                'perkara_id' => $perkara->id,
                'user_id' => $userId,
                'actor_type' => $actorType,
                'tahap' => $newTahap,
                'status_kegiatan' => $eventName,
                'tanggal_kejadian' => $tanggal,
                'catatan' => $catatan,
            ]);
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
