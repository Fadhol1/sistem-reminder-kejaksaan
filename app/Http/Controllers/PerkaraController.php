<?php

namespace App\Http\Controllers;

use App\Models\Perkara;
use App\Models\PerkaraTimeline;
use App\Services\PerkaraWorkflowService;
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
        $query = Perkara::with('tersangkas')->orderBy('created_at', 'desc');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('nomor_perkara', 'like', "%{$search}%")
                ->orWhere('tersangkas', function ($query) use ($search) {
                    $query->where('nama', 'like', "%{$search}%");
                });
        }

        if ($request->has('tahap') && $request->get('tahap') != '') {
            $query->where('tahap_saat_ini', $request->get('tahap'));
        }

        $perkaras = $query->paginate(10);

        return view('perkara.index', compact('perkaras'));
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
            'nomor_perkara' => 'required|string|unique:perkaras,nomor_perkara,'.$perkara->id,
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
        $perkara = Perkara::with(['timelines.user', 'activeReminders'])->findOrFail($id);

        // Ensure reminder status_urgensi is up to date (sync if needed)
        foreach ($perkara->activeReminders as $reminder) {
            $reminder->syncStatusUrgensi();
        }

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
