<?php

namespace App\Http\Controllers;

use App\Models\Perkara;
use App\Models\PerkaraReminder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Update status_urgensi for all active reminders first to ensure stats are fresh
        $activeReminders = PerkaraReminder::active()->get();
        foreach ($activeReminders as $reminder) {
            $reminder->syncStatusUrgensi();
        }

        $totalPerkara = Perkara::count();
        $perkaraAktif = Perkara::where('status_saat_ini', '!=', 'Tahap II Selesai')->count();
        
        $totalReminderAktif = PerkaraReminder::active()->count();
        $reminderH3 = PerkaraReminder::active()->where('status_urgensi', 'H-3')->count();
        $reminderOverdue = PerkaraReminder::active()->whereIn('status_urgensi', ['Jatuh Tempo', 'Overdue'])->count();

        // Ambil list perkara yang overdue atau mendesak untuk quick-view
        $urgentReminders = PerkaraReminder::with('perkara')
            ->active()
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