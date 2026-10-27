<?php

namespace App\Http\Controllers;

use App\Models\Perkara;
use App\Models\PerkaraReminder;
use App\Services\SLAEngineService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Evaluasi SLA otomatis (termasuk auto-transition jika ada yang overdue)
        SLAEngineService::runThrottled();

        $perkaraQuery = Perkara::query();
        if ($user->isJaksa()) {
            $perkaraQuery->whereHas('jaksas', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            });
        }

        $totalPerkara = (clone $perkaraQuery)->count();
        $perkaraAktif = (clone $perkaraQuery)
            ->where('status_saat_ini', '!=', 'Tahap II Selesai')
            ->where('status_saat_ini', 'not like', '%FORM-3%')
            ->where('status_saat_ini', 'not like', '%P-20%')
            ->where('status_saat_ini', 'not like', '%FORM-7%')
            ->count();

        $reminderQuery = PerkaraReminder::active();
        if ($user->isJaksa()) {
            $reminderQuery->whereHas('perkara.jaksas', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            });
        }

        $totalReminderAktif = (clone $reminderQuery)->count();
        $reminderH3 = (clone $reminderQuery)->where('status_urgensi', 'H-3')->count();
        $reminderOverdue = (clone $reminderQuery)->whereIn('status_urgensi', ['Jatuh Tempo', 'Overdue'])->count();

        // Ambil list perkara yang overdue atau mendesak untuk quick-view
        $urgentReminders = (clone $reminderQuery)->with('perkara.tersangkas')
            ->whereIn('status_urgensi', ['H-3', 'Jatuh Tempo', 'Overdue'])
            ->orderBy('deadline', 'asc')
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'totalPerkara',
            'perkaraAktif',
            'totalReminderAktif',
            'reminderH3',
            'reminderOverdue',
            'urgentReminders'
        ));
    }
}
