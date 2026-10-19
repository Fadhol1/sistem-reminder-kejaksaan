<?php

namespace App\Http\Controllers;

use App\Models\Perkara;
use App\Models\PerkaraTimeline;
use App\Services\PerkaraWorkflowService;
use App\Services\SLAEngineService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PerkaraController extends Controller
{
    /**
     * Display a listing of the perkara.
     */
    public function index(Request $request)
    {
        SLAEngineService::runThrottled();

        $user = $request->user();

        $query = Perkara::with(['tersangkas', 'jaksas', 'activeReminders'])->orderBy('created_at', 'desc');

        // 1. Role filtering (Jaksa & Penyidik hanya melihat perkaranya)
        if ($user && ($user->isJaksa() || $user->isPenyidik())) {
            $query->where(function ($q) use ($user) {
                if ($user->isJaksa()) {
                    $q->whereHas('jaksas', function ($sub) use ($user) {
                        $sub->where('users.id', $user->id);
                    });
                } else {
                    $q->where('penyidik', $user->name);
                }
            });
        }

        // 2. Search filtering (nomor_perkara, tersangka, jaksa, penyidik)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor_perkara', 'like', "%{$search}%")
                    ->orWhere('jaksa', 'like', "%{$search}%")
                    ->orWhere('penyidik', 'like', "%{$search}%")
                    ->orWhereHas('tersangkas', function ($subQ) use ($search) {
                        $subQ->where('nama', 'like', "%{$search}%");
                    })
                    ->orWhereHas('jaksas', function ($jQ) use ($search) {
                        $jQ->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // 3. Filter berdasarkan Tahapan
        if ($request->filled('tahap')) {
            $query->where('tahap_saat_ini', $request->tahap);
        }

        // 4. Filter Status Perkara (Aktif vs Selesai)
        if ($request->filled('status_filter')) {
            if ($request->status_filter === 'aktif') {
                $query->where('status_saat_ini', '!=', 'Tahap II Selesai');
            } elseif ($request->status_filter === 'selesai') {
                $query->where('status_saat_ini', 'Tahap II Selesai');
            }
        }

        // 5. Ringkasan Statistik Perkara (scoped sesuai role user)
        $summaryQuery = Perkara::query();
        if ($user && ($user->isJaksa() || $user->isPenyidik())) {
            $summaryQuery->where(function ($q) use ($user) {
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
            'total_perkara' => (clone $summaryQuery)->count(),
            'perkara_aktif' => (clone $summaryQuery)->where('status_saat_ini', '!=', 'Tahap II Selesai')->count(),
            'ada_overdue'   => (clone $summaryQuery)->whereHas('activeReminders', function ($rQ) {
                $rQ->where('status_urgensi', 'Overdue');
            })->count(),
            'tahap_selesai' => (clone $summaryQuery)->where('status_saat_ini', 'Tahap II Selesai')->count(),
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

        $perkaras = $query->paginate(10)->withQueryString();

        return view('perkara.index', compact('perkaras', 'stats', 'tahapList'));
    }

    /**
     * Show the form for creating a new perkara.
     */
    public function create(Request $request)
    {
        if (!$request->user()->isPidum()) {
            abort(403, 'Akses Ditolak: Hanya Admin PIDUM yang dapat membuat perkara baru.');
        }

        $jaksas = \App\Models\User::where('role', 'jaksa')->orderBy('name')->get();
        $penyidiks = \App\Models\User::where('role', 'penyidik')->orderBy('name')->get();

        return view('perkara.create', compact('jaksas', 'penyidiks'));
    }

    /**
     * Store a newly created perkara in storage.
     */
    public function store(Request $request)
    {
        if (!$request->user()->isPidum()) {
            abort(403, 'Akses Ditolak: Hanya Admin PIDUM yang dapat menyimpan perkara baru.');
        }

        $validated = $request->validate([
            'nomor_perkara' => 'required|string|unique:perkaras,nomor_perkara',
            'tanggal_spdp' => 'required|date',
            'tersangkas' => 'required|array|min:1',
            'tersangkas.*.nama' => 'required|string|max:255',
            'penyidik' => 'required|string',
            'jaksas' => 'required|array|min:1',
            'jaksas.*' => 'exists:users,id',
        ]);

        DB::transaction(function () use ($validated) {
            $firstJaksa = \App\Models\User::find($validated['jaksas'][0])->name ?? 'Multiple Jaksa';

            $perkara = Perkara::create([
                'nomor_perkara' => $validated['nomor_perkara'],
                'tanggal_spdp' => $validated['tanggal_spdp'],
                'penyidik' => $validated['penyidik'],
                'jaksa' => $firstJaksa, // Field lama tetap diisi sebatas fallback
                'bidang' => 'PIDUM', // Hardcode for now as per requirement
                'tahap_saat_ini' => PerkaraWorkflowService::TAHAP_KOORDINASI,
                'status_saat_ini' => 'Menunggu Koordinasi',
            ]);

            // Sync Multiple Jaksas to Pivot
            $perkara->jaksas()->sync($validated['jaksas']);

            //SImpan semua tersangka
            foreach ($validated['tersangkas'] as $tersangkaData) {
                $perkara->tersangkas()->create([
                    'nama' => $tersangkaData['nama'],
                ]);
            }

            // Create initial timeline
            PerkaraTimeline::create([
                'perkara_id' => $perkara->id,
                'user_id' => Auth::id(),
                'tahap' => PerkaraWorkflowService::TAHAP_KOORDINASI,
                'status_kegiatan' => 'Pendaftaran Perkara (Mulai Koordinasi Awal)',
                'tanggal_kejadian' => $perkara->created_at,
                'catatan' => 'Pendaftaran awal perkara ke sistem',
            ]);

            // Create Initial Reminder (max 3 days for Koordinasi Awal SLA)
            PerkaraWorkflowService::createReminder($perkara, PerkaraWorkflowService::TAHAP_KOORDINASI, 'SLA Koordinasi Awal', 3);
        });

        return redirect()->route('perkara.index')->with('success', 'Perkara berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the specified perkara.
     */
    public function edit(Request $request, string $id)
    {
        if (!$request->user()->isPidum()) {
            abort(403, 'Akses Ditolak: Hanya Admin PIDUM yang dapat mengedit perkara.');
        }

        $perkara = Perkara::with('tersangkas')->findOrFail($id);

        return view('perkara.edit', compact('perkara'));
    }

    /**
     * Update the specified perkara in storage.
     */
    public function update(Request $request, string $id)
    {
        if (!$request->user()->isPidum()) {
            abort(403, 'Akses Ditolak: Hanya Admin PIDUM yang dapat mengedit perkara.');
        }

        $perkara = Perkara::findOrFail($id);

        $validated = $request->validate([
            'nomor_perkara' => 'required|string|unique:perkaras,nomor_perkara,' . $perkara->id,
            'tersangkas' => 'required|array|min:1',
            'tersangkas.*.nama' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $perkara) {
            $perkara->update([
                'nomor_perkara' => $validated['nomor_perkara'],
            ]);

            // Sync Tersangka (Delete old ones and replace with new ones to simplify editing)
            $perkara->tersangkas()->delete();
            foreach ($validated['tersangkas'] as $tersangkaData) {
                $perkara->tersangkas()->create([
                    'nama' => $tersangkaData['nama'],
                ]);
            }
        });

        return redirect()->route('perkara.show', $perkara->id)->with('success', 'Perkara berhasil diperbarui.');
    }

    /**
     * Display the specified perkara.
     */
    public function show(string $id)
    {
        SLAEngineService::runThrottled();

        $perkara = Perkara::with(['timelines.user', 'activeReminders'])->findOrFail($id);

        $availableActions = PerkaraWorkflowService::getAvailableActions($perkara);

        return view('perkara.show', compact('perkara', 'availableActions'));
    }

    /**
     * Catat perkembangan perkara / eksekusi workflow action.
     */
    public function catatPerkembangan(Request $request, string $id)
    {
        if (!$request->user()->isPidum()) {
            abort(403, 'Akses Ditolak: Hanya Admin PIDUM yang dapat mencatat perkembangan perkara.');
        }

        $perkara = Perkara::findOrFail($id);

        $validated = $request->validate([
            'action_id' => 'required|string',
            'tanggal' => 'required|date',
            'catatan' => 'nullable|string',
        ]);

        // Validate if action is valid
        $availableActions = PerkaraWorkflowService::getAvailableActions($perkara);
        $actionExists = collect($availableActions)->contains('id', $validated['action_id']);

        if (!$actionExists) {
            return redirect()->back()->with('error', 'Aksi tidak valid untuk tahap dan status saat ini.');
        }

        try {
            PerkaraWorkflowService::processAction(
                $perkara,
                $validated['action_id'],
                $validated,
                Auth::id()
            );
            return redirect()->route('perkara.show', $perkara->id)->with('success', 'Perkembangan perkara berhasil dicatat.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified perkara from storage.
     */
    public function destroy(Request $request, string $id)
    {
        if (!$request->user()->isPidum()) {
            abort(403, 'Akses Ditolak: Hanya Admin PIDUM yang dapat menghapus perkara.');
        }

        $perkara = Perkara::findOrFail($id);

        DB::transaction(function () use ($perkara) {
            // Detach jaksas (Many-to-Many pivot)
            $perkara->jaksas()->detach();

            // Delete related records (assuming cascade isn't fully enforced or to be safe)
            $perkara->tersangkas()->delete();
            $perkara->timelines()->delete();
            $perkara->activeReminders()->delete();

            // Delete the main record
            $perkara->delete();
        });

        return redirect()->route('perkara.index')->with('success', 'Perkara berhasil dihapus.');
    }
}
