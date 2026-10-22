<?php

namespace App\Services\Fcm;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmSenderService
{
    protected ?string $projectId;
    protected ?string $credentialsPath;
    protected string $fcmScope = 'https://www.googleapis.com/auth/firebase.messaging';

    public function __construct(?string $projectId = null, ?string $credentialsPath = null)
    {
        $this->projectId = $projectId ?: config('services.firebase.project_id', 'reminderskejaksaan');
        $this->credentialsPath = $credentialsPath ?: config('services.firebase.credentials_path');

        if ($this->credentialsPath && !str_starts_with($this->credentialsPath, '/') && !str_contains($this->credentialsPath, ':')) {
            $this->credentialsPath = base_path($this->credentialsPath);
        }
    }

    /**
     * Dapatkan OAuth 2.0 access token untuk FCM HTTP v1.
     * Hasil token di-cache sesuai masa berlakunya (default 50 menit).
     */
    public function getAccessToken(): ?string
    {
        $cacheKey = 'fcm_http_v1_access_token_' . md5($this->credentialsPath ?? '');

        return Cache::remember($cacheKey, now()->addMinutes(50), function () {
            if (!$this->credentialsPath || !file_exists($this->credentialsPath)) {
                Log::warning('FcmSenderService: Service account credentials file tidak ditemukan di: ' . ($this->credentialsPath ?? 'null'));
                return null;
            }

            try {
                $credentials = new ServiceAccountCredentials(
                    [$this->fcmScope],
                    $this->credentialsPath
                );

                $authToken = $credentials->fetchAuthToken();

                if (isset($authToken['access_token'])) {
                    return $authToken['access_token'];
                }

                Log::error('FcmSenderService: Gagal mendapatkan access token dari Google Service Account.');
                return null;
            } catch (\Throwable $e) {
                Log::error('FcmSenderService: Exception saat fetch OAuth token: ' . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Kirim pesan push notification ke satu token FCM perangkat.
     *
     * @param string $deviceToken Token FCM perangkat penerima
     * @param string $title Judul notifikasi
     * @param string $body Isi notifikasi
     * @param array $data Data payload (key-value strings)
     * @return array [
     *     'success' => bool,
     *     'message_id' => ?string,
     *     'error' => ?string,
     *     'is_invalid_token' => bool
     * ]
     */
    public function sendToToken(string $deviceToken, string $title, string $body, array $data = []): array
    {
        $token = trim($deviceToken);
        if (empty($token)) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => 'Device token is empty.',
                'is_invalid_token' => true,
            ];
        }

        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return [
                'success' => false,
                'message_id' => null,
                'error' => 'Gagal mendapatkan OAuth 2.0 server access token.',
                'is_invalid_token' => false,
            ];
        }

        // Pastikan semua nilai data payload adalah string (persyaratan FCM HTTP v1)
        $stringifiedData = [];
        foreach ($data as $key => $value) {
            $stringifiedData[(string)$key] = is_scalar($value) ? (string)$value : json_encode($value);
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $stringifiedData,
                'android' => [
                    'priority' => 'HIGH',
                    'notification' => [
                        'sound' => 'default',
                        'click_action' => 'OPEN_PERKARA_DETAIL',
                        'channel_id' => 'sipeta_reminder_channel',
                    ],
                ],
            ],
        ];

        try {
            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->asJson()
                ->post($url, $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                $messageId = $responseData['name'] ?? null;

                return [
                    'success' => true,
                    'message_id' => $messageId,
                    'error' => null,
                    'is_invalid_token' => false,
                ];
            }

            // Tangani respons kegagalan dari FCM
            $statusCode = $response->status();
            $errorJson = $response->json();
            $errorMessage = $errorJson['error']['message'] ?? 'FCM Request failed with status ' . $statusCode;
            $errorCode = $errorJson['error']['details'][0]['errorCode'] ?? ($errorJson['error']['status'] ?? '');

            $isInvalidToken = in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT'])
                || $statusCode === 404
                || str_contains(strtolower($errorMessage), 'not registered')
                || str_contains(strtolower($errorMessage), 'invalid registration token');

            Log::warning('FcmSenderService: Pengiriman FCM gagal.', [
                'status_code' => $statusCode,
                'error_code' => $errorCode,
                'is_invalid_token' => $isInvalidToken,
                'masked_token' => self::maskToken($token),
            ]);

            return [
                'success' => false,
                'message_id' => null,
                'error' => $errorMessage,
                'is_invalid_token' => $isInvalidToken,
            ];
        } catch (\Throwable $e) {
            Log::error('FcmSenderService: Exception saat mengirim pesan FCM: ' . $e->getMessage(), [
                'masked_token' => self::maskToken($token),
            ]);

            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage(),
                'is_invalid_token' => false,
            ];
        }
    }

    /**
     * Sensor token untuk keperluan log agar tidak membocorkan data sensitif.
     */
    public static function maskToken(string $token): string
    {
        $len = strlen($token);
        if ($len <= 10) {
            return '***';
        }

        return substr($token, 0, 6) . '...' . substr($token, -4);
    }
}
