<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserDeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    /**
     * Daftarkan atau perbarui token perangkat FCM untuk pengguna terautentikasi.
     * User ID diambil secara ketat dari session/token Sanctum (tidak dari request body).
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fcm_token' => ['required', 'string', 'min:10', 'max:4096'],
            'device_id' => ['nullable', 'string', 'max:100'],
            'device_type' => ['nullable', 'string', 'in:android,ios,web'],
        ]);

        $user = $request->user();
        $token = trim($validated['fcm_token']);
        $deviceId = $validated['device_id'] ?? null;
        $deviceType = $validated['device_type'] ?? 'android';
        $tokenHash = UserDeviceToken::hashToken($token);

        // 1. Cek apakah token FCM ini sudah pernah terdaftar (misal pada user lain atau sesi lama)
        $existingToken = UserDeviceToken::where('fcm_token_hash', $tokenHash)->first();

        if ($existingToken) {
            // Rotasi token ke user yang sedang login saat ini
            $existingToken->update([
                'user_id' => $user->id,
                'device_id' => $deviceId ?: $existingToken->device_id,
                'device_type' => $deviceType,
                'is_active' => true,
                'last_used_at' => now(),
            ]);

            $deviceToken = $existingToken;
        } else {
            // Jika ada device_id yang sama untuk user ini, nonaktifkan token lama perangkat tersebut
            if ($deviceId) {
                UserDeviceToken::where('user_id', $user->id)
                    ->where('device_id', $deviceId)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }

            // Buat entri token baru
            $deviceToken = UserDeviceToken::create([
                'user_id' => $user->id,
                'device_id' => $deviceId,
                'device_type' => $deviceType,
                'fcm_token' => $token,
                'fcm_token_hash' => $tokenHash,
                'is_active' => true,
                'last_used_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Token perangkat FCM berhasil didaftarkan.',
            'data' => [
                'id' => $deviceToken->id,
                'device_id' => $deviceToken->device_id,
                'device_type' => $deviceToken->device_type,
                'is_active' => (bool)$deviceToken->is_active,
                'updated_at' => $deviceToken->updated_at->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Cabut (nonaktifkan) token perangkat FCM saat pengguna logout atau mencabut izin.
     */
    public function revoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'fcm_token' => ['nullable', 'string', 'required_without:device_id'],
            'device_id' => ['nullable', 'string', 'required_without:fcm_token'],
        ]);

        $user = $request->user();
        $query = UserDeviceToken::where('user_id', $user->id);

        if (!empty($validated['fcm_token'])) {
            $tokenHash = UserDeviceToken::hashToken(trim($validated['fcm_token']));
            $query->where('fcm_token_hash', $tokenHash);
        } elseif (!empty($validated['device_id'])) {
            $query->where('device_id', $validated['device_id']);
        }

        $affected = $query->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => $affected > 0
                ? 'Token perangkat berhasil dicabut.'
                : 'Tidak ada token aktif yang cocok untuk dicabut.',
        ], 200);
    }
}
