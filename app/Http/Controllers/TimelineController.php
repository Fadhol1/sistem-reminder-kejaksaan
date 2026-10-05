<?php

namespace App\Http\Controllers;

use App\Models\PerkaraTimeline;
use Illuminate\Http\Request;

class TimelineController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = PerkaraTimeline::with(['perkara' => function($q) {
            $q->with('tersangkas');
        }, 'user'])->orderBy('tanggal_kejadian', 'desc')->orderBy('created_at', 'desc');

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

        // Search filtering (by nomor perkara or action text)
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('status_kegiatan', 'like', "%{$search}%")
                  ->orWhere('catatan', 'like', "%{$search}%")
                  ->orWhereHas('perkara', function ($subQ) use ($search) {
                      $subQ->where('nomor_perkara', 'like', "%{$search}%")
                           ->orWhereHas('tersangkas', function ($tQ) use ($search) {
                               $tQ->where('nama', 'like', "%{$search}%");
                           });
                  });
            });
        }

        $timelines = $query->paginate(30);

        return view('timelines.index', compact('timelines'));
    }
}
