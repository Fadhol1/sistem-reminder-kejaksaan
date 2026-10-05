<?php

namespace App\Http\Controllers;

use App\Models\PerkaraReminder;
use Illuminate\Http\Request;

class ReminderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Sync urgensi for all active reminders globally (maybe resource intensive in very large DB, but okay for MVP)
        $activeRemindersToSync = PerkaraReminder::active()->get();
        foreach ($activeRemindersToSync as $rem) {
            $rem->syncStatusUrgensi();
        }

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

        // Sorting: nearest deadline first (and then overdue at the very top theoretically, although overdue means ascending deadline too)
        $reminders = $query->orderBy('deadline', 'asc')->paginate(20);

        return view('reminders.index', compact('reminders'));
    }
}
