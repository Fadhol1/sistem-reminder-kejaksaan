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
        $query = Perkara::query()->orderBy('created_at', 'desc');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where('nomor_perkara', 'like', "%{$search}%")
                  ->orWhere('nama_tersangka', 'like', "%{$search}%");
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
    public function create()
    {
        // Check if user is pidum (handled by middleware or directly)
        return view('perkara.create');
    }

    /**
     * Store a newly created perkara in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nomor_perkara' => 'required|string|unique:perkaras,nomor_perkara',
            'tanggal_spdp' => 'required|date',
            'nama_tersangka' => 'required|string',
            'penyidik' => 'required|string',
            'jaksa' => 'required|string',
        ]);

        DB::transaction(function () use ($validated) {
            $perkara = Perkara::create([
                'nomor_perkara' => $validated['nomor_perkara'],
                'tanggal_spdp' => $validated['tanggal_spdp'],
                'nama_tersangka' => $validated['nama_tersangka'],
                'penyidik' => $validated['penyidik'],
                'jaksa' => $validated['jaksa'],
                'bidang' => 'PIDUM', // Hardcode for now as per requirement
                'tahap_saat_ini' => PerkaraWorkflowService::TAHAP_SPDP,
                'status_saat_ini' => 'Menunggu Penerimaan SPDP',
            ]);

            // Create initial timeline
            PerkaraTimeline::create([
                'perkara_id' => $perkara->id,
                'user_id' => Auth::id(),
                'tahap' => PerkaraWorkflowService::TAHAP_SPDP,
                'status_kegiatan' => 'Pendaftaran Perkara (SPDP)',
                'tanggal_kejadian' => $perkara->created_at,
                'catatan' => 'Pendaftaran awal perkara ke sistem',
            ]);

            // Create Initial Reminder (max 7 days for SPDP Receipt SLA)
            PerkaraWorkflowService::createReminder($perkara, PerkaraWorkflowService::TAHAP_SPDP, 'SLA Penerimaan SPDP', 7);
        });

        return redirect()->route('perkara.index')->with('success', 'Perkara berhasil ditambahkan.');
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
}
