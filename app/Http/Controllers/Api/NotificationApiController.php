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
     * Mendukung filter: event_type, status, today=1, days=7, date=YYYY-MM-DD
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

        // Filter berdasarkan tanggal jika diminta
        if ($request->boolean('today') || $request->input('filter') === 'today') {
            $query->whereDate('created_at', now()->toDateString());
        } elseif ($request->filled('days')) {
            $days = (int) $request->input('days', 7);
            $query->where('created_at', '>=', now()->subDays($days)->startOfDay());
        } elseif ($request->filled('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        $perPage = (int) $request->input('per_page', 30);
        $logs = $query->paginate($perPage);

        $todayCount = PushNotificationLog::where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat notifikasi berhasil dimuat.',
            'summary' => [
                'today_count' => $todayCount,
                'total'       => $logs->total(),
            ],
            'data'    => $logs,
        ]);
    }
}