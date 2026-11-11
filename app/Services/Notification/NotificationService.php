<?php

namespace App\Services\Notification;

use App\Models\Perkara;
use App\Models\PerkaraReminder;
use App\Models\PushNotificationLog;
use App\Models\User;
use App\Models\UserDeviceToken;
use App\Services\Fcm\FcmSenderService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public const EVENT_REMINDER_H3 = 'REMINDER_H3';
    public const EVENT_REMINDER_H0 = 'REMINDER_H0';
    public const EVENT_REMINDER_OVERDUE = 'REMINDER_OVERDUE';

    public const EVENT_AUTO_P17 = 'AUTO_TRANSITION_P17';
    public const EVENT_AUTO_FORM2 = 'AUTO_TRANSITION_FORM2';
    public const EVENT_AUTO_FORM3 = 'AUTO_TRANSITION_FORM3';
    public const EVENT_AUTO_P20 = 'AUTO_TRANSITION_P20';
    public const EVENT_AUTO_FORM7 = 'AUTO_TRANSITION_FORM7';
    public const EVENT_AUTO_KOORDINASI = 'AUTO_TRANSITION_KOORDINASI';
    public const EVENT_STAGE_TRANSITION = 'STAGE_TRANSITION';

    protected FcmSenderService $fcmSender;

    public function __construct(?FcmSenderService $fcmSender = null)
    {
        $this->fcmSender = $fcmSender ?: app(FcmSenderService::class);
    }

    /**
     * Dapatkan daftar penerima yang berhak menerima notifikasi berdasarkan relasi perkara dan event.
     * Aturan bisnis ketat:
     * - Jaksa terkait selalu menerima pengingat dan auto-transition perkaranya.
     * - Admin PIDUM menerima pemberitahuan eskalasi Overdue dan Auto-Transition terminal.
     * - Penyidik menerima tembusan saat surat resmi (P-17, FORM-2, FORM-3, P-20, FORM-7) diterbitkan.
     *
     * @return Collection<int, User>
     */
    public function resolveRecipients(Perkara $perkara, string $eventType): Collection
    {
        $recipients = collect();

        // 1. Jaksa terkait (dari pivot perkara_jaksa dan fallback nama jaksa)
        $jaksas = $perkara->jaksas()->get();
        if ($jaksas->isNotEmpty()) {
            $recipients = $recipients->merge($jaksas);
        } elseif ($perkara->jaksaUser) {
            $recipients->push($perkara->jaksaUser);
        }

        // 2. Admin PIDUM (pengawas umum untuk perkara Overdue atau transisi arsip/pengembalian)
        $pidumEvents = [
            self::EVENT_REMINDER_OVERDUE,
            self::EVENT_AUTO_FORM3,
            self::EVENT_AUTO_P20,
            self::EVENT_AUTO_FORM7,
            self::EVENT_AUTO_KOORDINASI,
        ];
        if (in_array($eventType, $pidumEvents)) {
            $pidumUsers = User::where('role', 'pidum')->get();
            $recipients = $recipients->merge($pidumUsers);
        }

        // Hilangkan duplikasi berdasarkan user ID
        return $recipients->unique('id')->values();
    }

    /**
     * Kirim notifikasi pengingat urgensi (H-3, H-0 / Jatuh Tempo, Overdue).
     * Menerapkan deduplikasi harian via idempotency_key agar tidak berulang kali dikirim saat SLA Engine dijalankan.
     */
    public function notifyReminderUrgency(PerkaraReminder $reminder, string $urgency): array
    {
        if (!in_array($urgency, ['H-3', 'Jatuh Tempo', 'Overdue'])) {
            return [];
        }

        $perkara = $reminder->perkara;
        if (!$perkara) {
            return [];
        }

        $eventType = match ($urgency) {
            'H-3' => self::EVENT_REMINDER_H3,
            'Jatuh Tempo' => self::EVENT_REMINDER_H0,
            'Overdue' => self::EVENT_REMINDER_OVERDUE,
        };

        $title = match ($urgency) {
            'H-3' => '⚠️ Reminder H-3: ' . $reminder->jenis_reminder,
            'Jatuh Tempo' => '🚨 Jatuh Tempo Hari Ini: ' . $reminder->jenis_reminder,
            'Overdue' => '⛔ Batas Waktu Terlewati (Overdue): ' . $reminder->jenis_reminder,
        };

        $deadlineStr = Carbon::parse($reminder->deadline)->translatedFormat('d M Y');
        $body = match ($urgency) {
            'H-3' => "Perkara {$perkara->nomor_perkara} mendekati batas waktu ({$deadlineStr}). Segera tindak lanjuti.",
            'Jatuh Tempo' => "Batas waktu perkara {$perkara->nomor_perkara} jatuh tempo hari ini ({$deadlineStr}).",
            'Overdue' => "Batas waktu perkara {$perkara->nomor_perkara} telah kedaluwarsa. Sistem mengevaluasi alur SLA.",
        };

        $recipients = $this->resolveRecipients($perkara, $eventType);
        $dateKey = now()->format('Y-m-d');
        $results = [];

        foreach ($recipients as $recipient) {
            // Idempotency Key: Unik per reminder, status urgensi, tanggal hari ini, dan user penerima
            $idempotencyKey = "rem_{$reminder->id}_{$urgency}_{$dateKey}_u{$recipient->id}";

            $result = $this->dispatchNotification(
                user: $recipient,
                perkara: $perkara,
                reminder: $reminder,
                eventType: $eventType,
                idempotencyKey: $idempotencyKey,
                title: $title,
                body: $body
            );

            $results[] = $result;
        }

        return $results;
    }

    /**
     * Kirim notifikasi saat terjadi auto-transition pada SLA Engine.
     * Menerapkan deduplikasi via idempotency_key agar peristiwa transisi hanya dikirim 1 kali.
     */
    public function notifyAutoTransition(Perkara $perkara, string $actionId): array
    {
        $eventTypeMap = [
            'catat_p17' => self::EVENT_AUTO_P17,
            'catat_p17_2' => self::EVENT_AUTO_FORM2,
            'catat_form3' => self::EVENT_AUTO_FORM3,
            'p20' => self::EVENT_AUTO_P20,
            'form7' => self::EVENT_AUTO_FORM7,
            'tindak_lanjut_koordinasi_overdue' => self::EVENT_AUTO_KOORDINASI,
        ];

        if (!isset($eventTypeMap[$actionId])) {
            return [];
        }

        $eventType = $eventTypeMap[$actionId];

        $title = match ($eventType) {
            self::EVENT_AUTO_P17 => '📄 Sistem Terbitkan P-17: ' . $perkara->nomor_perkara,
            self::EVENT_AUTO_FORM2 => '📄 Sistem Terbitkan SOP FORM-2 (P-17 Kedua)',
            self::EVENT_AUTO_FORM3 => '🗄️ SOP FORM-3: Pengembalian SPDP & Simpan Arsip',
            self::EVENT_AUTO_P20 => '🗄️ P-20 Diterbitkan: Pengembalian SPDP & Arsip',
            self::EVENT_AUTO_FORM7 => '🗄️ SOP FORM-7 Diterbitkan: Berkas & SPDP Dikembalikan',
            self::EVENT_AUTO_KOORDINASI => '⚠️ Tindak Lanjut Form-1B & Form-1C Terbit Otomatis',
        };

        $body = match ($eventType) {
            self::EVENT_AUTO_P17 => "Batas 30 hari SPDP terlewati tanpa berkas. Surat P-17 diterbitkan otomatis ke penyidik.",
            self::EVENT_AUTO_FORM2 => "Batas waktu 30 hari P-17 terlewati. SOP FORM-2 (P-17 Kedua) diterbitkan otomatis ke penyidik.",
            self::EVENT_AUTO_FORM3 => "Batas waktu 30 hari SOP FORM-2 terlewati. Berkas SPDP dikembalikan ke penyidik dan perkara diarsipkan.",
            self::EVENT_AUTO_P20 => "Batas 14 hari pemenuhan petunjuk (P-19) habis. P-20 diterbitkan, SPDP dikembalikan ke penyidik.",
            self::EVENT_AUTO_FORM7 => "Batas 14 hari Tahap II (P-21) habis. SOP FORM-7 diterbitkan, Berkas & SPDP dikembalikan ke penyidik.",
            self::EVENT_AUTO_KOORDINASI => "Batas waktu 3 hari koordinasi awal terlewati. Laporan Form-1B & Form-1C dicatatkan otomatis.",
        };

        $recipients = $this->resolveRecipients($perkara, $eventType);
        $dateKey = now()->format('Y-m-d');
        $results = [];

        foreach ($recipients as $recipient) {
            $idempotencyKey = "trans_{$actionId}_p{$perkara->id}_{$dateKey}_u{$recipient->id}";

            $result = $this->dispatchNotification(
                user: $recipient,
                perkara: $perkara,
                reminder: null,
                eventType: $eventType,
                idempotencyKey: $idempotencyKey,
                title: $title,
                body: $body
            );

            $results[] = $result;
        }

        return $results;
    }

        /**
     * Kirim notifikasi saat perkara berlanjut ke langkah atau tahap berikutnya.
     */
    public function notifyStageTransition(Perkara $perkara, string $stageOrEventName, ?string $catatan = null, ?int $actorUserId = null): array
    {
        $eventType = self::EVENT_STAGE_TRANSITION;
        $title = '📌 Perkembangan Perkara: ' . $perkara->nomor_perkara;
        $body = 'Perkara telah berlanjut ke: ' . $stageOrEventName . ($catatan ? ' (' . $catatan . ')' : '');

        $recipients = $this->resolveRecipients($perkara, $eventType);

        if ($recipients->isEmpty()) {
            if ($perkara->jaksaUser) {
                $recipients->push($perkara->jaksaUser);
            } elseif ($actorUserId) {
                $actor = User::find($actorUserId);
                if ($actor) {
                    $recipients->push($actor);
                }
            }
        }

        $dateKey = now()->format('Y-m-d');
        $stageSlug = \Illuminate\Support\Str::slug($perkara->tahap_saat_ini . '_' . $stageOrEventName);
        $results = [];

        foreach ($recipients as $recipient) {
            $idempotencyKey = "stage_p{$perkara->id}_{$stageSlug}_{$dateKey}_u{$recipient->id}";

            $result = $this->dispatchNotification(
                user: $recipient,
                perkara: $perkara,
                reminder: null,
                eventType: $eventType,
                idempotencyKey: $idempotencyKey,
                title: $title,
                body: $body
            );

            $results[] = $result;
        }

        return $results;
    }

    /**
     * Pusat eksekusi pengiriman notifikasi dengan pencatatan audit trail, penanganan token, dan idempotensi.
     */
    protected function dispatchNotification(
        User $user,
        Perkara $perkara,
        ?PerkaraReminder $reminder,
        string $eventType,
        string $idempotencyKey,
        string $title,
        string $body
    ): array {
        // 1. Data payload minimal (non-sensitif) agar aplikasi Android mengambil detail via authenticated API
        $dataPayload = [
            'perkara_id' => (string)$perkara->id,
            'nomor_perkara' => (string)$perkara->nomor_perkara,
            'tahap' => (string)$perkara->tahap_saat_ini,
            'event_type' => (string)$eventType,
            'reminder_id' => $reminder ? (string)$reminder->id : '',
            'click_action' => 'OPEN_PERKARA_DETAIL',
        ];

        // 2. Atomic Pre-Locking Idempotensi (Mencegah pengiriman duplikat di tingkat database)
        $existingLog = PushNotificationLog::where('idempotency_key', $idempotencyKey)->first();

        if ($existingLog) {
            if ($existingLog->status === PushNotificationLog::STATUS_FAILED) {
                // Atomic Compare-and-Swap: Hanya 1 proses yang berhasil mengubah status FAILED -> PENDING
                $affected = PushNotificationLog::where('id', $existingLog->id)
                    ->where('status', PushNotificationLog::STATUS_FAILED)
                    ->update(['status' => PushNotificationLog::STATUS_PENDING, 'error_message' => null]);

                if ($affected === 0) {
                    Log::info("NotificationService: Retry race hit untuk key '{$idempotencyKey}'. Melewati pengiriman duplikat.");
                    return [
                        'status' => 'SKIPPED_DUPLICATE',
                        'user_id' => $user->id,
                        'idempotency_key' => $idempotencyKey,
                    ];
                }

                $log = $existingLog->fresh();
            } elseif ($existingLog->status === PushNotificationLog::STATUS_NO_TOKEN) {
                // Jika sebelumnya NO_TOKEN, periksa apakah pengguna sekarang sudah mendaftarkan token aktif
                if ($user->activeDeviceTokens()->exists()) {
                    // Atomic Compare-and-Swap: Hanya 1 proses yang berhasil mengubah status NO_TOKEN -> PENDING
                    $affected = PushNotificationLog::where('id', $existingLog->id)
                        ->where('status', PushNotificationLog::STATUS_NO_TOKEN)
                        ->update(['status' => PushNotificationLog::STATUS_PENDING, 'error_message' => null]);

                    if ($affected === 0) {
                        Log::info("NotificationService: Retry race hit (NO_TOKEN) untuk key '{$idempotencyKey}'. Melewati pengiriman duplikat.");
                        return [
                            'status' => 'SKIPPED_DUPLICATE',
                            'user_id' => $user->id,
                            'idempotency_key' => $idempotencyKey,
                        ];
                    }

                    $log = $existingLog->fresh();
                } else {
                    // Pengguna masih belum memiliki token aktif -> Tolak duplikasi/skip
                    Log::info("NotificationService: Pengguna {$user->id} masih belum memiliki token untuk key '{$idempotencyKey}'. Melewati pengiriman.");
                    return [
                        'status' => PushNotificationLog::STATUS_NO_TOKEN,
                        'log_id' => $existingLog->id,
                        'user_id' => $user->id,
                    ];
                }
            } else {
                // Sudah SENT atau PENDING -> Tolak duplikasi
                Log::info("NotificationService: Idempotency hit untuk key '{$idempotencyKey}'. Melewati pengiriman duplikat.");
                return [
                    'status' => 'SKIPPED_DUPLICATE',
                    'user_id' => $user->id,
                    'idempotency_key' => $idempotencyKey,
                ];
            }
        } else {
            try {
                $log = PushNotificationLog::create([
                    'user_id' => $user->id,
                    'perkara_id' => $perkara->id,
                    'perkara_reminder_id' => $reminder?->id,
                    'event_type' => $eventType,
                    'idempotency_key' => $idempotencyKey,
                    'title' => $title,
                    'body' => $body,
                    'data_payload' => $dataPayload,
                    'status' => PushNotificationLog::STATUS_PENDING,
                ]);
            } catch (\Throwable $e) {
                $isUniqueViolation = $e instanceof \Illuminate\Database\UniqueConstraintViolationException
                    || ($e instanceof \Illuminate\Database\QueryException && (
                        $e->getCode() === '23000' ||
                        (isset($e->errorInfo[1]) && in_array($e->errorInfo[1], [1062, 19], true))
                    ));

                if ($isUniqueViolation) {
                    // Tertangkap race condition jika proses paralel lain baru saja meng-insert key yang sama
                    Log::info("NotificationService: Idempotency race hit untuk key '{$idempotencyKey}'. Melewati pengiriman duplikat.");
                    return [
                        'status' => 'SKIPPED_DUPLICATE',
                        'user_id' => $user->id,
                        'idempotency_key' => $idempotencyKey,
                    ];
                }

                throw $e;
            }
        }

        // 3. Ambil seluruh token aktif milik pengguna
        $activeTokens = $user->activeDeviceTokens()->get();

        if ($activeTokens->isEmpty()) {
            $log->update([
                'status' => PushNotificationLog::STATUS_NO_TOKEN,
                'error_message' => 'Pengguna belum mendaftarkan perangkat aktif.',
            ]);

            return [
                'status' => PushNotificationLog::STATUS_NO_TOKEN,
                'log_id' => $log->id,
                'user_id' => $user->id,
            ];
        }

        $sentCount = 0;
        $invalidTokenCount = 0;
        $lastMessageId = null;
        $lastErrorMessage = null;

        // 4. Kirim ke setiap perangkat aktif milik pengguna
        foreach ($activeTokens as $deviceToken) {
            $response = $this->fcmSender->sendToToken(
                deviceToken: $deviceToken->fcm_token,
                title: $title,
                body: $body,
                data: $dataPayload
            );

            if ($response['success']) {
                $sentCount++;
                $lastMessageId = $response['message_id'];
                $deviceToken->update(['last_used_at' => now()]);
            } else {
                $lastErrorMessage = $response['error'];

                // Jika FCM mengindikasikan token tidak terdaftar / invalid, nonaktifkan token tersebut
                if ($response['is_invalid_token']) {
                    $invalidTokenCount++;
                    $deviceToken->update(['is_active' => false]);
                    Log::info("NotificationService: Token perangkat ID {$deviceToken->id} dinonaktifkan karena ditolak oleh FCM.");
                }
            }
        }

        // 5. Tentukan status log akhir dan perbarui baris yang telah di-lock PENDING
        $finalStatus = PushNotificationLog::STATUS_FAILED;
        if ($sentCount > 0) {
            $finalStatus = PushNotificationLog::STATUS_SENT;
        } elseif ($invalidTokenCount > 0 && $invalidTokenCount === $activeTokens->count()) {
            $finalStatus = PushNotificationLog::STATUS_INVALID_TOKEN;
        }

        $log->update([
            'fcm_message_id' => $lastMessageId,
            'status' => $finalStatus,
            'error_message' => $lastErrorMessage,
            'sent_at' => ($sentCount > 0) ? now() : null,
        ]);

        return [
            'status' => $finalStatus,
            'log_id' => $log->id,
            'user_id' => $user->id,
            'sent_count' => $sentCount,
        ];
    }
}
