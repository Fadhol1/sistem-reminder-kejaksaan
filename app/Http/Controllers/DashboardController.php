<?php

namespace App\Http\Controllers;

use App\Models\Perkara;
use App\Models\PerkaraReminder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        // Update status_urgensi for all active reminders first to ensure stats are fresh
        $activeReminders = PerkaraReminder::active()->get();
        foreach ($activeReminders as $reminder) {
            $reminder->syncStatusUrgensi();
        }

        $perkaraQuery = Perkara::query();
        if ($user->isJaksa()) {
            $perkaraQuery->whereHas('jaksas', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            });
        } elseif ($user->isPenyidik()) {
            $perkaraQuery->where('penyidik', $user->name);
        }

        $totalPerkara = (clone $perkaraQuery)->count();
        $perkaraAktif = (clone $perkaraQuery)->where('status_saat_ini', '!=', 'Tahap II Selesai')->count();

        $reminderQuery = PerkaraReminder::active();
        if ($user->isJaksa() || $user->isPenyidik()) {
            $reminderQuery->whereHas('perkara', function ($query) use ($user) {
                if ($user->isJaksa()) {
                    $query->whereHas('jaksas', function ($q) use ($user) {
                        $q->where('users.id', $user->id);
                    });
                } else {
                    $query->where('penyidik', $user->name);
                }
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
