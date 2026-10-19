<?php

namespace App\Http\Controllers;

use App\Models\PerkaraTimeline;
use App\Services\PerkaraWorkflowService;
use Illuminate\Http\Request;

class TimelineController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = PerkaraTimeline::with([
            'perkara' => function ($q) {
                $q->with(['tersangkas', 'jaksas']);
            },
            'user'
        ])->orderBy('tanggal_kejadian', 'desc')->orderBy('id', 'desc');

        // 1. Role filtering (Jaksa/Penyidik scoping)
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

        // 2. Search filtering
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('status_kegiatan', 'like', "%{$search}%")
                    ->orWhere('catatan', 'like', "%{$search}%")
                    ->orWhere('tahap', 'like', "%{$search}%")
                    ->orWhereHas('perkara', function ($subQ) use ($search) {
                        $subQ->where('nomor_perkara', 'like', "%{$search}%")
                            ->orWhere('jaksa', 'like', "%{$search}%")
                            ->orWhere('penyidik', 'like', "%{$search}%")
                            ->orWhereHas('tersangkas', function ($tQ) use ($search) {
                                $tQ->where('nama', 'like', "%{$search}%");
                            });
                    });
            });
        }

        // 3. Filter berdasarkan Tahapan
        if ($request->filled('tahap')) {
            $query->where('tahap', $request->tahap);
        }

        // 4. Filter berdasarkan Aktor (SYSTEM vs USER)
        if ($request->filled('actor')) {
            $query->where('actor_type', $request->actor);
        }

        // 5. Filter Rentang Tanggal
        if ($request->filled('tanggal_dari')) {
            $query->whereDate('tanggal_kejadian', '>=', $request->tanggal_dari);
        }
        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('tanggal_kejadian', '<=', $request->tanggal_sampai);
        }

        // 6. Ringkasan Statistik Audit Trail (scoped sesuai role user)
        $summaryQuery = PerkaraTimeline::query();
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
            'total_aktivitas' => (clone $summaryQuery)->count(),
            'hari_ini'        => (clone $summaryQuery)->whereDate('tanggal_kejadian', now()->toDateString())->count(),
            'oleh_sistem'     => (clone $summaryQuery)->where('actor_type', 'SYSTEM')->count(),
            'oleh_petugas'    => (clone $summaryQuery)->where(function ($q) {
                $q->where('actor_type', 'USER')->orWhereNull('actor_type');
            })->count(),
        ];

        // Daftar tahapan baku untuk dropdown filter
        $tahapList = [
            PerkaraWorkflowService::TAHAP_KOORDINASI,
            PerkaraWorkflowService::TAHAP_MONITORING,
            PerkaraWorkflowService::TAHAP_P24,
            PerkaraWorkflowService::TAHAP_P19,
            PerkaraWorkflowService::TAHAP_P21,
            PerkaraWorkflowService::TAHAP_II,
        ];

        $timelines = $query->paginate(10)->withQueryString();

        return view('timelines.index', compact('timelines', 'stats', 'tahapList'));
    }
}
