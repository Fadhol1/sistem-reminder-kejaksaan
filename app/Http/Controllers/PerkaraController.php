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

        $query = Perkara::with(['tersangkas', 'penyidiks', 'jaksas', 'activeReminders'])->orderBy('created_at', 'desc');

        // 1. Role filtering (Jaksa hanya melihat perkaranya di pivot)
        if ($user && $user->isJaksa()) {
            $query->whereHas('jaksas', function ($sub) use ($user) {
                $sub->where('users.id', $user->id);
            });
        }

        // 2. Search filtering (nomor_perkara, tersangka, jaksa, penyidik)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor_perkara', 'like', "%{$search}%")
                    ->orWhere('jaksa', 'like', "%{$search}%")
                    ->orWhere('penyidik', 'like', "%{$search}%")
                    ->orWhereHas('penyidiks', function ($pQ) use ($search) {
                        $pQ->where('nama', 'like', "%{$search}%");
                    })
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
                $query->where('status_saat_ini', '!=', 'Tahap II Selesai')
                      ->where('status_saat_ini', 'not like', '%FORM-3%')
                      ->where('status_saat_ini', 'not like', '%P-20%')
                      ->where('status_saat_ini', 'not like', '%FORM-7%');
            } elseif ($request->status_filter === 'selesai') {
                $query->where(function ($q) {
                    $q->where('status_saat_ini', 'Tahap II Selesai')
                      ->orWhere('status_saat_ini', 'like', '%FORM-3%')
                      ->orWhere('status_saat_ini', 'like', '%P-20%')
                      ->orWhere('status_saat_ini', 'like', '%FORM-7%');
                });
            }
        }

        // 5. Ringkasan Statistik Perkara (scoped sesuai role user)
        $summaryQuery = Perkara::query();
        if ($user && $user->isJaksa()) {
            $summaryQuery->whereHas('jaksas', function ($sub) use ($user) {
                $sub->where('users.id', $user->id);
            });
        }

        $stats = [
            'total_perkara' => (clone $summaryQuery)->count(),
            'perkara_aktif' => (clone $summaryQuery)
                ->where('status_saat_ini', '!=', 'Tahap II Selesai')
                ->where('status_saat_ini', 'not like', '%FORM-3%')
                ->where('status_saat_ini', 'not like', '%P-20%')
                ->where('status_saat_ini', 'not like', '%FORM-7%')
                ->count(),
            'ada_overdue'   => (clone $summaryQuery)->whereHas('activeReminders', function ($rQ) {
                $rQ->where('status_urgensi', 'Overdue');
            })->count(),
            'tahap_selesai' => (clone $summaryQuery)->where(function ($q) {
                $q->where('status_saat_ini', 'Tahap II Selesai')
                  ->orWhere('status_saat_ini', 'like', '%FORM-3%')
                  ->orWhere('status_saat_ini', 'like', '%P-20%')
                  ->orWhere('status_saat_ini', 'like', '%FORM-7%');
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

        return view('perkara.create', compact('jaksas'));
    }

    /**
     * Store a newly created perkara in storage.
     */
    public function store(Request $request)
    {
        if (!$request->user()->isPidum()) {
            abort(403, 'Akses Ditolak: Hanya Admin PIDUM yang dapat menyimpan perkara baru.');
        }

        // Normalisasi jika input dikirim sebagai single string 'penyidik'
        if (!$request->has('penyidiks') && $request->filled('penyidik')) {
            $request->merge([
                'penyidiks' => [
                    ['nama' => $request->input('penyidik')]
                ]
            ]);
        }

        $validated = $request->validate([
            'nomor_perkara' => 'required|string|unique:perkaras,nomor_perkara',
            'tanggal_spdp' => 'required|date',
            'tersangkas' => 'required|array|min:1',
            'tersangkas.*.nama' => 'required|string|max:255',
            'penyidiks' => 'required|array|min:1',
            'penyidiks.*.nama' => 'required|string|max:255',
            'penyidik' => 'nullable|string|max:255',
            'jaksas' => 'required|array|min:1',
            'jaksas.*' => 'exists:users,id',
        ], [
            'nomor_perkara.required' => 'Nomor perkara / SPDP wajib diisi.',
            'nomor_perkara.unique' => 'Nomor perkara sudah terdaftar di sistem.',
            'penyidiks.required' => 'Daftar penyidik wajib diisi.',
            'penyidiks.min' => 'Minimal harus ada satu penyidik.',
            'penyidiks.*.nama.required' => 'Nama / Instansi penyidik wajib diisi.',
            'tersangkas.required' => 'Daftar tersangka wajib diisi.',
            'tersangkas.min' => 'Minimal harus ada satu tersangka.',
            'tersangkas.*.nama.required' => 'Nama tersangka wajib diisi.',
            'jaksas.required' => 'Pilih minimal satu Jaksa Penuntut Umum.',
            'jaksas.min' => 'Pilih minimal satu Jaksa Penuntut Umum.',
        ]);

        DB::transaction(function () use ($validated) {
            $firstJaksa = \App\Models\User::find($validated['jaksas'][0])->name ?? 'Multiple Jaksa';

            $penyidikNames = collect($validated['penyidiks'])->pluck('nama')->filter()->values();
            $summaryPenyidik = $penyidikNames->implode(', ');

            $perkara = Perkara::create([
                'nomor_perkara' => $validated['nomor_perkara'],
                'tanggal_spdp' => $validated['tanggal_spdp'],
                'penyidik' => $summaryPenyidik,
                'jaksa' => $firstJaksa, // Field lama tetap diisi sebatas fallback
                'bidang' => 'PIDUM', // Hardcode for now as per requirement
                'tahap_saat_ini' => PerkaraWorkflowService::TAHAP_KOORDINASI,
                'status_saat_ini' => 'Menunggu Koordinasi',
            ]);

            // Sync Multiple Jaksas to Pivot
            $perkara->jaksas()->sync($validated['jaksas']);

            // Simpan semua tersangka
            foreach ($validated['tersangkas'] as $tersangkaData) {
                $perkara->tersangkas()->create([
                    'nama' => $tersangkaData['nama'],
                ]);
            }

            // Simpan semua penyidik
            foreach ($penyidikNames as $pNama) {
                $perkara->penyidiks()->create([
                    'nama' => $pNama,
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

        $perkara = Perkara::with(['tersangkas', 'penyidiks', 'jaksas'])->findOrFail($id);
        $jaksas = \App\Models\User::where('role', 'jaksa')->orderBy('name')->get();
        $selectedJaksaIds = $perkara->jaksas->pluck('id')->toArray();

        return view('perkara.edit', compact('perkara', 'jaksas', 'selectedJaksaIds'));
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

        // Normalisasi jika input dikirim sebagai single string 'penyidik'
        if (!$request->has('penyidiks') && $request->filled('penyidik')) {
            $request->merge([
                'penyidiks' => [
                    ['nama' => $request->input('penyidik')]
                ]
            ]);
        }

        $validated = $request->validate([
            'nomor_perkara' => 'required|string|unique:perkaras,nomor_perkara,' . $perkara->id,
            'penyidiks' => 'required|array|min:1',
            'penyidiks.*.nama' => 'required|string|max:255',
            'penyidik' => 'nullable|string|max:255',
            'jaksas' => 'required|array|min:1',
            'jaksas.*' => 'exists:users,id',
            'tersangkas' => 'required|array|min:1',
            'tersangkas.*.nama' => 'required|string|max:255',
        ], [
            'nomor_perkara.required' => 'Nomor perkara / SPDP wajib diisi.',
            'nomor_perkara.unique' => 'Nomor perkara sudah terdaftar di sistem.',
            'penyidiks.required' => 'Daftar penyidik wajib diisi.',
            'penyidiks.min' => 'Minimal harus ada satu penyidik.',
            'penyidiks.*.nama.required' => 'Nama / Instansi penyidik wajib diisi.',
            'jaksas.required' => 'Pilih minimal satu Jaksa Penuntut Umum.',
            'jaksas.min' => 'Pilih minimal satu Jaksa Penuntut Umum.',
            'tersangkas.required' => 'Daftar tersangka wajib diisi.',
            'tersangkas.min' => 'Minimal harus ada satu tersangka.',
            'tersangkas.*.nama.required' => 'Nama tersangka wajib diisi.',
        ]);

        DB::transaction(function () use ($validated, $perkara) {
            $firstJaksa = \App\Models\User::find($validated['jaksas'][0])->name ?? 'Multiple Jaksa';

            $penyidikNames = collect($validated['penyidiks'])->pluck('nama')->filter()->values();
            $summaryPenyidik = $penyidikNames->implode(', ');

            $perkara->update([
                'nomor_perkara' => $validated['nomor_perkara'],
                'penyidik' => $summaryPenyidik,
                'jaksa' => $firstJaksa, // Fallback kolom string lama
            ]);

            // Sync Jaksas di tabel pivot perkara_jaksa
            $perkara->jaksas()->sync($validated['jaksas']);

            // Sync Tersangka (Delete old ones and replace with new ones to simplify editing)
            $perkara->tersangkas()->delete();
            foreach ($validated['tersangkas'] as $tersangkaData) {
                $perkara->tersangkas()->create([
                    'nama' => $tersangkaData['nama'],
                ]);
            }

            // Sync Penyidik
            $perkara->penyidiks()->delete();
            foreach ($penyidikNames as $pNama) {
                $perkara->penyidiks()->create([
                    'nama' => $pNama,
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

        $perkara = Perkara::with(['timelines.user', 'activeReminders', 'tersangkas', 'penyidiks', 'jaksas'])->findOrFail($id);

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

    /**
     * [DEV TOOLS] Simulasi manipulasi waktu & pengujian alur SLA lokal.
     * Hanya aktif jika environment adalah local atau config('app.debug') true.
     */
    public function devSimulateTime(Request $request, string $id)
    {
        if (!app()->environment('local') && !config('app.debug')) {
            abort(403, 'Fitur simulasi waktu hanya dapat diakses pada environment lokal / development.');
        }

        $perkara = Perkara::findOrFail($id);
        $mode = $request->input('mode'); // set_h3, set_h0, set_overdue, custom_days, trigger_engine
        $customDays = (int) $request->input('custom_days', 0);

        if ($mode === 'trigger_engine') {
            app(\App\Services\SLAEngineService::class)->executeEngine();
            $perkara->refresh();
            return redirect()->route('perkara.show', $perkara->id)
                ->with('success', '[DEV SIMULASI] SLA Engine berhasil dieksekusi secara instan! Status terkini: "' . $perkara->status_saat_ini . '"');
        }

        if ($mode === 'reset_tahap') {
            $target = $request->input('target_tahap');
            // Tutup semua reminder aktif lama
            $perkara->activeReminders()->update(['is_active' => false]);

            if ($target === 'P-19') {
                $perkara->tahap_saat_ini = \App\Services\PerkaraWorkflowService::TAHAP_P19;
                $perkara->status_saat_ini = 'Menunggu Pemenuhan Petunjuk';
                $perkara->save();

                \App\Services\PerkaraWorkflowService::createReminder(
                    $perkara,
                    \App\Services\PerkaraWorkflowService::TAHAP_P19,
                    'SLA Pemenuhan Petunjuk',
                    14
                );

                $message = '[DEV SIMULASI] Perkara berhasil diset ke Tahap P-19 (Menunggu Pemenuhan Petunjuk) dengan reminder SLA 14 Hari aktif.';
            } elseif ($target === 'P-21') {
                $perkara->tahap_saat_ini = \App\Services\PerkaraWorkflowService::TAHAP_P21;
                $perkara->status_saat_ini = 'Menunggu Penyerahan Tahap II';
                $perkara->save();

                \App\Services\PerkaraWorkflowService::createReminder(
                    $perkara,
                    \App\Services\PerkaraWorkflowService::TAHAP_P21,
                    'SLA Penyerahan Tahap II',
                    14
                );

                $message = '[DEV SIMULASI] Perkara berhasil diset ke Tahap P-21 (Menunggu Penyerahan Tahap II) dengan reminder SLA 14 Hari aktif.';
            } elseif ($target === 'Monitoring') {
                $perkara->tahap_saat_ini = \App\Services\PerkaraWorkflowService::TAHAP_MONITORING;
                $perkara->status_saat_ini = 'Menunggu Berkas Tahap I';
                $perkara->tanggal_spdp = now();
                $perkara->save();

                \App\Services\PerkaraWorkflowService::createReminder(
                    $perkara,
                    \App\Services\PerkaraWorkflowService::TAHAP_MONITORING,
                    'Batas Waktu Penyerahan Tahap I (30 Hari)',
                    30
                );

                $message = '[DEV SIMULASI] Perkara berhasil diset ke Tahap Monitoring Penyidikan (Menunggu Berkas Tahap I) dengan reminder SLA 30 Hari aktif.';
            } else {
                $message = '[DEV SIMULASI] Target tahap reset tidak dikenali.';
            }

            return redirect()->route('perkara.show', $perkara->id)->with('success', $message);
        }

        $activeReminder = $perkara->activeReminders()->first();

        // Jika tidak ada active reminder tapi mode set_overdue pada Menunggu Berkas Tahap I, kita tetap bisa jalankan simulasi SPDP > 30 hari
        if (!$activeReminder && $mode !== 'set_overdue') {
            return redirect()->route('perkara.show', $perkara->id)
                ->with('error', '[DEV SIMULASI] Tidak ditemukan reminder aktif pada perkara ini untuk dimanipulasi.');
        }

        $message = '';

        switch ($mode) {
            case 'set_h3':
                if ($activeReminder) {
                    $activeReminder->deadline = now()->addDays(3)->setTime(12, 0, 0);
                    $activeReminder->status_urgensi = 'H-3';
                    $activeReminder->save();
                    $message = '[DEV SIMULASI] Deadline diubah menjadi H-3 (' . $activeReminder->deadline->translatedFormat('d F Y H:i') . ').';
                }
                break;

            case 'set_h0':
                if ($activeReminder) {
                    $activeReminder->deadline = now()->endOfDay();
                    $activeReminder->status_urgensi = 'Jatuh Tempo';
                    $activeReminder->save();
                    $message = '[DEV SIMULASI] Deadline diubah menjadi Hari Ini / Jatuh Tempo (' . $activeReminder->deadline->translatedFormat('d F Y') . ').';
                }
                break;

            case 'set_overdue':
                if ($activeReminder) {
                    $activeReminder->deadline = now()->subDays(1);
                    $activeReminder->status_urgensi = 'Overdue';
                    $activeReminder->save();
                }

                // Jika di tahap Monitoring Penyidikan awal, mundurkan juga tanggal_spdp agar selaras (> 30 hari)
                if ($perkara->tahap_saat_ini === \App\Services\PerkaraWorkflowService::TAHAP_MONITORING && $perkara->status_saat_ini === 'Menunggu Berkas Tahap I') {
                    $perkara->tanggal_spdp = now()->subDays(35);
                    $perkara->save();
                }

                // Eksekusi SLA Engine langsung agar terjadi auto-transition seketika
                app(\App\Services\SLAEngineService::class)->executeEngine();
                $perkara->refresh();

                if (str_contains($perkara->status_saat_ini, 'P-20')) {
                    $message = '[DEV SIMULASI] Batas waktu 14 hari P-19 terlewati! SLA Engine otomatis menerbitkan "P-20 (Waktu Penyidikan Tambahan Habis / Pengembalian SPDP)". Status perkara Diarsipkan & seluruh reminder dinonaktifkan.';
                } elseif (str_contains($perkara->status_saat_ini, 'FORM-7')) {
                    $message = '[DEV SIMULASI] Batas waktu 14 hari P-21 terlewati! SLA Engine otomatis menerbitkan "SOP FORM-7 (Pengembalian Berkas & SPDP ke Penyidik)". Status perkara Diarsipkan & seluruh reminder dinonaktifkan.';
                } elseif (str_contains($perkara->status_saat_ini, 'FORM-3')) {
                    $message = '[DEV SIMULASI] Batas waktu 30 hari SOP FORM-2 terlewati! SLA Engine otomatis menerbitkan "SOP FORM-3 (Pengembalian SPDP)". Status perkara Diarsipkan & seluruh reminder dinonaktifkan.';
                } elseif (str_contains($perkara->status_saat_ini, 'FORM-2')) {
                    $message = '[DEV SIMULASI] Batas waktu 30 hari P-17 terlewati! SLA Engine otomatis menerbitkan "SOP FORM-2 (P-17 Kedua)".';
                } elseif (str_contains($perkara->status_saat_ini, 'P-17')) {
                    $message = '[DEV SIMULASI] Batas waktu 30 hari SPDP terlewati! SLA Engine otomatis menerbitkan "P-17 (Permintaan Perkembangan Penyidikan)".';
                } else {
                    $message = '[DEV SIMULASI] Deadline diset Overdue! SLA Engine langsung mengeksekusi alur otomatis. Status perkara kini: "' . $perkara->status_saat_ini . '".';
                }
                break;

            case 'custom_days':
                if ($activeReminder) {
                    $activeReminder->deadline = now()->addDays($customDays)->setTime(12, 0, 0);
                    $activeReminder->status_urgensi = \App\Models\PerkaraReminder::determineUrgensi($activeReminder->deadline);
                    $activeReminder->save();

                    // Jika custom days menghasilkan overdue (negatif), eksekusi SLA Engine
                    if ($activeReminder->status_urgensi === 'Overdue') {
                        app(\App\Services\SLAEngineService::class)->executeEngine();
                        $perkara->refresh();
                        if (str_contains($perkara->status_saat_ini, 'P-20')) {
                            $message = '[DEV SIMULASI] Deadline diset ' . $customDays . ' hari (Overdue >14 hari). SLA Engine otomatis menerbitkan "P-20 (Pengembalian SPDP / Arsip)".';
                        } elseif (str_contains($perkara->status_saat_ini, 'FORM-7')) {
                            $message = '[DEV SIMULASI] Deadline diset ' . $customDays . ' hari (Overdue >14 hari). SLA Engine otomatis menerbitkan "SOP FORM-7 (Pengembalian Berkas & SPDP / Arsip)".';
                        } else {
                            $message = '[DEV SIMULASI] Deadline diset ' . $customDays . ' hari (Overdue). SLA Engine otomatis dieksekusi. Status kini: "' . $perkara->status_saat_ini . '".';
                        }
                    } else {
                        $message = '[DEV SIMULASI] Deadline diset ' . $customDays . ' hari lagi (' . $activeReminder->deadline->translatedFormat('d F Y') . '). Status Urgensi: ' . $activeReminder->status_urgensi . '.';
                    }
                }
                break;

            default:
                $message = '[DEV SIMULASI] Mode simulasi tidak dikenal.';
                break;
        }

        return redirect()->route('perkara.show', $perkara->id)->with('success', $message);
    }
}
