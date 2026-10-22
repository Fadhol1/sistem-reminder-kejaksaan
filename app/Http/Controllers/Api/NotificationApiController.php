<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushNotificationLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationApiController extends Controller
{
    /**
     * Mengambil riwayat push notification untuk pengguna terautentikasi saat ini.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = PushNotificationLog::where('user_id', $user->id)
            ->with(['perkara:id,nomor_perkara,tahap_saat_ini,status_saat_ini'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->input('event_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $perPage = (int) $request->input('per_page', 20);
        $logs = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Riwayat notifikasi berhasil dimuat.',
            'data' => $logs,
        ]);
    }
}
