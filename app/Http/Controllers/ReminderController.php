<?php

namespace App\Http\Controllers;

use App\Models\PerkaraReminder;
use App\Services\SLAEngineService;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Evaluasi SLA otomatis (termasuk auto-transition jika ada yang overdue)
        SLAEngineService::runThrottled();

        $query = PerkaraReminder::with(['perkara' => function($q) {
            $q->with('tersangkas');
        }])->active();

        // Role filtering
        if ($user->isJaksa() || $user->isPenyidik()) {
            $query->whereHas('perkara', function ($q) use ($user) {
                if ($user->isJaksa()) {
                    $q->whereHas('jaksas', function ($sub) use ($user) {
                        $sub->where('users.id', $user->id);
                    });
                } else {
                    $q->where('penyidik', $user->name);
                }
            });
        }

        // Search filtering (by nomor perkara or tersangka)
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->whereHas('perkara', function ($q) use ($search) {
                $q->where('nomor_perkara', 'like', "%{$search}%")
                  ->orWhereHas('tersangkas', function ($subQ) use ($search) {
                      $subQ->where('nama', 'like', "%{$search}%");
                  });
            });
        }

        // Urgency filter
        if ($request->has('urgensi') && $request->urgensi != '') {
            if ($request->urgensi == 'semua_mendesak') {
                $query->whereIn('status_urgensi', ['H-3', 'Jatuh Tempo', 'Overdue']);
            } else {
                $query->where('status_urgensi', $request->urgensi);
            }
        }

        // Ringkasan Statistik Urgensi Reminder Aktif (scoped sesuai role user)
        $summaryQuery = PerkaraReminder::query()->active();
        if ($user->isJaksa() || $user->isPenyidik()) {
            $summaryQuery->whereHas('perkara', function ($q) use ($user) {
                if ($user->isJaksa()) {
                    $q->whereHas('jaksas', function ($sub) use ($user) {
                        $sub->where('users.id', $user->id);
                    });
                } else {
                    $q->where('penyidik', $user->name);
                }
            });
        }

        $stats = [
            'total_aktif' => (clone $summaryQuery)->count(),
            'overdue'     => (clone $summaryQuery)->where('status_urgensi', 'Overdue')->count(),
            'jatuh_tempo' => (clone $summaryQuery)->where('status_urgensi', 'Jatuh Tempo')->count(),
            'h_3'         => (clone $summaryQuery)->where('status_urgensi', 'H-3')->count(),
            'aman'        => (clone $summaryQuery)->where('status_urgensi', 'Aman')->count(),
        ];

        // Sorting: Urutkan deadline terdekat dan pagination 10 data per halaman
        $reminders = $query->orderBy('deadline', 'asc')->paginate(10)->withQueryString();

        return view('reminders.index', compact('reminders', 'stats'));
    }
}
