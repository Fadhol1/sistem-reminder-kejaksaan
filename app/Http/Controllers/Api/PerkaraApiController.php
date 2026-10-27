<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Perkara;
use App\Models\PerkaraReminder;
use App\Models\PerkaraTimeline;
use App\Services\SLAEngineService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PerkaraApiController extends Controller
{
    /**
     * 1. INDEX: Mengembalikan daftar semua perkara dalam format JSON,
     * lengkap dengan relasi tersangkas dan reminder aktif.
     * Selalu menggunakan pagination untuk kompatibilitas dengan Android (PerkaraResponse).
     */
    public function index(Request $request): JsonResponse
    {
        SLAEngineService::runThrottled();

        $query = Perkara::with(['tersangkas', 'activeReminders', 'latestTimeline'])->orderBy('created_at', 'desc');

        // Filter berdasarkan role user (jaksa/penyidik)
        $user = $request->user();
        if ($user) {
            if ($user->role === 'jaksa') {
                $query->whereHas('jaksas', function ($q) use ($user) {
                    $q->where('users.id', $user->id);
                });
            } elseif ($user->role === 'penyidik') {
                $query->where('penyidik', $user->name);
            }
        }

        // Filter pencarian berdasarkan nomor perkara atau nama tersangka (via relasi)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nomor_perkara', 'like', "%{$search}%")
                    ->orWhereHas('tersangkas', function ($subQ) use ($search) {
                        $subQ->where('nama', 'like', "%{$search}%");
                    });
            });
        }

        // Filter berdasarkan tahap
        if ($request->filled('tahap')) {
            $query->where('tahap_saat_ini', $request->input('tahap'));
        }

        // Filter berdasarkan status kegiatan
        if ($request->filled('status')) {
            $query->where('status_saat_ini', $request->input('status'));
        }

        // Filter berdasarkan Jaksa atau Penyidik (manual filter via query param)
        if ($request->filled('jaksa')) {
            $query->whereHas('jaksas', function ($q) use ($request) {
                $q->where('users.name', 'like', "%{$request->input('jaksa')}%");
            });
        }
        if ($request->filled('penyidik')) {
            $query->where('penyidik', 'like', "%{$request->input('penyidik')}%");
        }

        // Selalu gunakan pagination (default 15 per halaman)
        $perPage = (int) $request->input('per_page', 15);
        $perkaras = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $perkaras,
        ]);
    }

    /**
     * 2. SHOW: Mengembalikan detail lengkap satu perkara berdasarkan ID
     * beserta seluruh riwayat timeline dan reminder-nya.
     */
    public function show(int|string $id): JsonResponse
    {
        SLAEngineService::runThrottled();

        $perkara = Perkara::with([
            'timelines' => function ($q) {
                $q->with('user:id,name,role')->orderBy('tanggal_kejadian', 'desc')->orderBy('id', 'desc');
            },
            'reminders' => function ($q) {
                $q->orderBy('deadline', 'asc')->orderBy('is_active', 'desc');
            },
            'activeReminders',
            'jaksas:id,name,email,role',
            'tersangkas',
        ])->find($id);

        if (!$perkara) {
            return response()->json([
                'success' => false,
                'message' => "Data perkara dengan ID {$id} tidak ditemukan.",
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail perkara berhasil dimuat.',
            'data' => $perkara,
        ]);
    }

    /**
     * GET TIMELINES: Mengembalikan daftar riwayat timeline untuk perkara tertentu.
     */
    public function getTimelines(int|string $id): JsonResponse
    {
        $perkara = Perkara::find($id);

        if (!$perkara) {
            return response()->json([
                'success' => false,
                'message' => "Data perkara dengan ID {$id} tidak ditemukan.",
                'data' => null,
            ], 404);
        }

        $timelines = PerkaraTimeline::with('user:id,name,role')
            ->where('perkara_id', $id)
            ->orderBy('tanggal_kejadian', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Riwayat timeline perkara berhasil dimuat.',
            'perkara' => [
                'id' => $perkara->id,
                'nomor_perkara' => $perkara->nomor_perkara,
                'tahap_saat_ini' => $perkara->tahap_saat_ini,
                'status_saat_ini' => $perkara->status_saat_ini,
            ],
            'data' => $timelines,
        ]);
    }

    /**
     * GET ALL TIMELINES: Mengembalikan seluruh timeline (Audit Trail) dengan filter role dan pagination.
     */
    public function getAllTimelines(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = PerkaraTimeline::with([
            'perkara:id,nomor_perkara,tahap_saat_ini,status_saat_ini,penyidik',
            'user:id,name,role'
        ])->orderBy('tanggal_kejadian', 'desc')->orderBy('id', 'desc');

        if ($user && ($user->isJaksa() || $user->isPenyidik())) {
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

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('status_kegiatan', 'like', "%{$search}%")
                    ->orWhere('catatan', 'like', "%{$search}%")
                    ->orWhereHas('perkara', function ($subQ) use ($search) {
                        $subQ->where('nomor_perkara', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = (int) $request->input('per_page', 20);
        $timelines = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar timeline berhasil dimuat.',
            'data' => $timelines,
        ]);
    }

    /**
     * 3. STORE: Menyimpan data perkara baru dengan validasi lengkap,
     * serta otomatis menginisialisasi timeline awal dan reminder SLA (opsional/default).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nomor_perkara' => 'required|string|max:255|unique:perkaras,nomor_perkara',
            'nama_tersangka' => 'required|string|max:255',
            'penyidik' => 'required|string|max:255',
            'jaksa' => 'required|string|max:255',
            'tanggal_spdp' => 'required|date',
            'tahap_saat_ini' => 'required|string|max:255',
            'status_saat_ini' => 'required|string|max:255',

            // Input tambahan opsional untuk catatan awal & reminder SLA
            'catatan' => 'nullable|string',
            'buat_reminder' => 'nullable|boolean',
            'jenis_reminder' => 'nullable|string|max:255',
            'deadline_days' => 'nullable|integer|min:1',
            'deadline' => 'nullable|date|after_or_equal:tanggal_spdp',
        ], [
            'nomor_perkara.required' => 'Nomor perkara wajib diisi.',
            'nomor_perkara.unique' => 'Nomor perkara sudah terdaftar di sistem.',
            'nama_tersangka.required' => 'Nama tersangka wajib diisi.',
            'penyidik.required' => 'Nama penyidik wajib diisi.',
            'jaksa.required' => 'Nama jaksa wajib diisi.',
            'tanggal_spdp.required' => 'Tanggal SPDP wajib diisi.',
            'tahap_saat_ini.required' => 'Tahap saat ini wajib diisi.',
            'status_saat_ini.required' => 'Status saat ini wajib diisi.',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            // 1. Simpan perkara utama
            $perkara = Perkara::create([
                'nomor_perkara' => $validated['nomor_perkara'],
                'penyidik' => $validated['penyidik'],
                'jaksa' => $validated['jaksa'],
                'tanggal_spdp' => $validated['tanggal_spdp'],
                'tahap_saat_ini' => $validated['tahap_saat_ini'],
                'status_saat_ini' => $validated['status_saat_ini'],
            ]);

            // Simpan data tersangka ke tabel tersangkas
            if (!empty($validated['nama_tersangka'])) {
                $perkara->tersangkas()->create([
                    'nama' => $validated['nama_tersangka'],
                ]);
            }

            // 2. Buat entri timeline perdana
            $perkara->timelines()->create([
                'tahap' => $perkara->tahap_saat_ini,
                'status_kegiatan' => $perkara->status_saat_ini,
                'tanggal_kejadian' => $perkara->tanggal_spdp,
                'catatan' => $validated['catatan'] ?? 'Penerimaan berkas SPDP awal.',
            ]);

            // 3. Inisialisasi reminder awal jika diminta atau secara default (SLA SPDP ke P-17: 30 hari)
            $buatReminder = $request->boolean('buat_reminder', true);
            if ($buatReminder) {
                $jenisReminder = $validated['jenis_reminder'] ?? 'Koordinasi / P-17 (SLA 30 Hari SPDP)';
                $deadlineDate = isset($validated['deadline'])
                    ? Carbon::parse($validated['deadline'])
                    : Carbon::parse($perkara->tanggal_spdp)->addDays($request->input('deadline_days', 30));

                $perkara->reminders()->create([
                    'jenis_reminder' => $jenisReminder,
                    'tanggal_mulai' => $perkara->tanggal_spdp,
                    'deadline' => $deadlineDate->toDateString(),
                    'status_urgensi' => PerkaraReminder::determineUrgensi($deadlineDate),
                    'is_active' => true,
                ]);
            }

            // Muat relasi lengkap untuk response
            $perkara->load(['latestTimeline', 'activeReminders']);

            return response()->json([
                'success' => true,
                'message' => 'Data perkara baru berhasil disimpan dan diinisialisasi.',
                'data' => $perkara,
            ], 201);
        });
    }

    public function destroy($id)
    {
        $perkara = Perkara::find($id);

        if (!$perkara) {
            return response()->json([
                'success' => false,
                'message' => 'Data perkara tidak ditemukan.'
            ], 404);
        }

        // Hapus data perkara (relasi timeline & reminder akan ikut terhapus jika di-set cascade, atau hapus manual)
        $perkara->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data perkara berhasil dihapus.'
        ], 200);
    }

    /**
     * 4. STORE TIMELINE: Mencatat perkembangan baru ke tabel perkara_timelines,
     * memperbarui status_saat_ini pada tabel perkaras, serta mengelola reminder terkait.
     */
    public function storeTimeline(Request $request, int|string|null $id = null): JsonResponse
    {
        // Mendukung ID dari URL parameter (/perkara/{id}/timeline) atau body parameter ({ perkara_id: 1 })
        $perkaraId = $id ?? $request->input('perkara_id');
        $request->merge(['perkara_id' => $perkaraId]);

        $validated = $request->validate([
            'perkara_id' => 'required|exists:perkaras,id',
            'tahap' => 'required|string|max:255',
            'status_kegiatan' => 'required|string|max:255',
            'tanggal_kejadian' => 'required|date',
            'catatan' => 'nullable|string',

            // Update status perkara opsional (default menggunakan tahap dan status_kegiatan baru)
            'update_status_perkara' => 'nullable|boolean',
            'tahap_saat_ini' => 'nullable|string|max:255',
            'status_saat_ini' => 'nullable|string|max:255',

            // Manajemen reminder baru / pengganti
            'create_reminder' => 'nullable|boolean',
            'close_previous_reminders' => 'nullable|boolean', // Tutup reminder aktif sebelumnya
            'jenis_reminder' => 'nullable|string|max:255',
            'tanggal_mulai' => 'nullable|date',
            'deadline' => 'nullable|date',
            'status_urgensi' => ['nullable', Rule::in(['Aman', 'H-3', 'Jatuh Tempo', 'Overdue'])],
            'is_active' => 'nullable|boolean',
        ], [
            'perkara_id.required' => 'ID Perkara wajib disertakan.',
            'perkara_id.exists' => 'Data perkara yang dipilih tidak ditemukan.',
            'tahap.required' => 'Tahap perkembangan perkara wajib diisi.',
            'status_kegiatan.required' => 'Status kegiatan wajib diisi.',
            'tanggal_kejadian.required' => 'Tanggal kejadian wajib diisi.',
        ]);

        return DB::transaction(function () use ($validated, $request, $perkaraId) {
            $perkara = Perkara::findOrFail($perkaraId);

            // 1. Simpan riwayat perkembangan ke perkara_timelines
            $timeline = $perkara->timelines()->create([
                'tahap' => $validated['tahap'],
                'status_kegiatan' => $validated['status_kegiatan'],
                'tanggal_kejadian' => $validated['tanggal_kejadian'],
                'catatan' => $validated['catatan'] ?? null,
            ]);

            // 2. Perbarui tahap_saat_ini dan status_saat_ini pada tabel perkaras
            $updateStatusPerkara = $request->boolean('update_status_perkara', true);
            if ($updateStatusPerkara) {
                $perkara->update([
                    'tahap_saat_ini' => $validated['tahap_saat_ini'] ?? $validated['tahap'],
                    'status_saat_ini' => $validated['status_saat_ini'] ?? $validated['status_kegiatan'],
                ]);
            }

            // Notifikasi perkembangan alur perkara
            try {
                $stageName = $validated['status_kegiatan'] ?? $validated['tahap'];
                app(\App\Services\Notification\NotificationService::class)->notifyStageTransition($perkara, $stageName, $validated['catatan'] ?? null, auth()->id());
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Gagal notif timeline: ' . $e->getMessage());
            }

            // 3. Kelola reminder terkait:
            // Nonaktifkan reminder aktif sebelumnya jika diinstruksikan atau jika ada reminder baru yang menggantikan
            $hasNewReminderData = $request->boolean('create_reminder') || !empty($validated['jenis_reminder']);
            $shouldClosePrevious = $request->boolean('close_previous_reminders', $hasNewReminderData);

            if ($shouldClosePrevious) {
                $perkara->reminders()->where('is_active', true)->update([
                    'is_active' => false,
                ]);
            }

            // Buat reminder baru jika data reminder disediakan
            $newReminder = null;
            if ($hasNewReminderData && !empty($validated['jenis_reminder'])) {
                $tglMulai = $validated['tanggal_mulai'] ?? $validated['tanggal_kejadian'];
                $deadline = $validated['deadline'] ?? Carbon::parse($tglMulai)->addDays(14)->toDateString();
                $urgensi = $validated['status_urgensi'] ?? PerkaraReminder::determineUrgensi($deadline);

                $newReminder = $perkara->reminders()->create([
                    'jenis_reminder' => $validated['jenis_reminder'],
                    'tanggal_mulai' => $tglMulai,
                    'deadline' => $deadline,
                    'status_urgensi' => $urgensi,
                    'is_active' => $request->boolean('is_active', true),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Perkembangan perkara berhasil dicatat, status perkara diperbarui, dan reminder dikelola.',
                'data' => [
                    'timeline' => $timeline,
                    'perkara' => $perkara->fresh(['latestTimeline', 'activeReminders']),
                    'new_reminder' => $newReminder,
                ],
            ], 201);
        });
    }

    /**
     * 5. GET REMINDERS: Mengembalikan daftar reminder aktif/mendesak
     * dengan dukungan pagination dan filter urgensi untuk Android SIPETA.
     */
    public function getReminders(Request $request): JsonResponse
    {
        SLAEngineService::runThrottled();

        $user = $request->user();

        // 1. Query builder dengan relasi perkara dan tersangkas (tanpa kolom nama_tersangka yang sudah deprecated)
        $query = PerkaraReminder::with([
            'perkara' => function ($q) {
                $q->select('id', 'nomor_perkara', 'penyidik', 'jaksa', 'tahap_saat_ini', 'status_saat_ini')
                    ->with('tersangkas');
            },
        ]);

        // 2. Filter data hanya milik user yang sedang login (Jaksa/Penyidik) jika login
        if ($user && $user->role === 'jaksa') {
            $query->whereHas('perkara.jaksas', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        } elseif ($user && $user->role === 'penyidik') {
            $query->whereHas('perkara', function ($q) use ($user) {
                $q->where('penyidik', $user->name);
            });
        }

        // 3. Filter aktif (default true, kecuali jika meminta 'all')
        if ($request->input('is_active') !== 'all') {
            $isActive = $request->boolean('is_active', true);
            $query->where('is_active', $isActive);
        }

        // 4. Dukungan filter parameter urgensi (semua_mendesak, Overdue, H-3, Aman)
        $urgensi = $request->input('urgensi') ?? $request->input('status_urgensi');
        if ($urgensi === 'semua_mendesak' || $request->boolean('urgent_only', false)) {
            $query->urgent(); // Mengambil status H-3, Jatuh Tempo, dan Overdue
        } elseif (!empty($urgensi) && strtolower($urgensi) !== 'semua' && strtolower($urgensi) !== 'all') {
            $urgensiList = explode(',', $urgensi);
            $query->whereIn('status_urgensi', array_map('trim', $urgensiList));
        }

        // 5. Filter pencarian perkara (nomor perkara, jaksa, atau tersangka)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('perkara', function ($q) use ($search) {
                $q->where('nomor_perkara', 'like', "%{$search}%")
                    ->orWhere('jaksa', 'like', "%{$search}%")
                    ->orWhereHas('tersangkas', function ($q2) use ($search) {
                        $q2->where('nama', 'like', "%{$search}%");
                    });
            });
        }

        // 6. Urutkan prioritas urgensi: Overdue -> Jatuh Tempo -> H-3 -> Aman, lalu deadline terdekat
        $query->orderByRaw("
            CASE status_urgensi
                WHEN 'Overdue' THEN 1
                WHEN 'Jatuh Tempo' THEN 2
                WHEN 'H-3' THEN 3
                WHEN 'Aman' THEN 4
                ELSE 5
            END ASC
        ")->orderBy('deadline', 'asc');

        // 7. Pagination (default 15 per halaman)
        $perPage = (int) $request->input('per_page', 15);
        $reminders = $query->paginate($perPage);

        // 8. Rekapitulasi ringkas statistik dashboard Android
        $summaryQuery = PerkaraReminder::where('is_active', true);
        if ($user && $user->role === 'jaksa') {
            $summaryQuery->whereHas('perkara.jaksas', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        } elseif ($user && $user->role === 'penyidik') {
            $summaryQuery->whereHas('perkara', function ($q) use ($user) {
                $q->where('penyidik', $user->name);
            });
        }

        $summary = [
            'total_active' => (clone $summaryQuery)->count(),
            'total_urgent' => (clone $summaryQuery)->whereIn('status_urgensi', ['H-3', 'Jatuh Tempo', 'Overdue'])->count(),
            'overdue'      => (clone $summaryQuery)->where('status_urgensi', 'Overdue')->count(),
            'jatuh_tempo'  => (clone $summaryQuery)->where('status_urgensi', 'Jatuh Tempo')->count(),
            'h_min_3'      => (clone $summaryQuery)->where('status_urgensi', 'H-3')->count(),
            'aman'         => (clone $summaryQuery)->where('status_urgensi', 'Aman')->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Daftar reminder berhasil dimuat.',
            'summary' => $summary,
            'data'    => $reminders,
        ]);
    }

    /**
     * 6. GET REMINDER LOGS: Mengembalikan seluruh riwayat/log reminder (termasuk yang sudah selesai/inaktif)
     * sebagai audit trail SLA yang bersifat permanen dan tidak dapat dihapus.
     */
    public function getReminderLogs(Request $request): JsonResponse
    {
        SLAEngineService::runThrottled();

        $user = $request->user();

        $query = PerkaraReminder::with([
            'perkara' => function ($q) {
                $q->select('id', 'nomor_perkara', 'penyidik', 'jaksa', 'tahap_saat_ini', 'status_saat_ini')
                    ->with('tersangkas');
            },
        ]);

        // 1. Role-based scoping (Jaksa hanya melihat perkara miliknya, Penyidik miliknya, PIDUM semua)
        if ($user && $user->role === 'jaksa') {
            $query->whereHas('perkara.jaksas', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        } elseif ($user && $user->role === 'penyidik') {
            $query->whereHas('perkara', function ($q) use ($user) {
                $q->where('penyidik', $user->name);
            });
        }

        // 2. Filter Status Log:
        // status = 'all' (default), 'selesai' / 'inactive', 'aktif' / 'active', 'overdue'
        $status = strtolower($request->input('status', 'all'));
        if ($status === 'selesai' || $status === 'inactive') {
            $query->where('is_active', false);
        } elseif ($status === 'aktif' || $status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'overdue') {
            $query->where('status_urgensi', 'Overdue');
        }

        // 3. Filter berdasarkan Tahap jika dikirimkan
        if ($request->filled('tahap')) {
            $query->where('tahap', $request->input('tahap'));
        }

        // 4. Pencarian (nomor perkara, nama tersangka, jaksa, atau jenis reminder)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('jenis_reminder', 'like', "%{$search}%")
                    ->orWhere('tahap', 'like', "%{$search}%")
                    ->orWhereHas('perkara', function ($subQ) use ($search) {
                        $subQ->where('nomor_perkara', 'like', "%{$search}%")
                            ->orWhere('jaksa', 'like', "%{$search}%")
                            ->orWhereHas('tersangkas', function ($tQ) use ($search) {
                                $tQ->where('nama', 'like', "%{$search}%");
                            });
                    });
            });
        }

        // 5. Urutan audit log: dari yang paling baru diupdate/dibuat ke yang terlama
        $query->orderBy('updated_at', 'desc')->orderBy('id', 'desc');

        // 6. Pagination (default 20 per halaman)
        $perPage = (int) $request->input('per_page', 20);
        $logs = $query->paginate($perPage);

        // 7. Ringkasan statistik untuk header kartu log di aplikasi mobile
        $summaryQuery = PerkaraReminder::query();
        if ($user && $user->role === 'jaksa') {
            $summaryQuery->whereHas('perkara.jaksas', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        } elseif ($user && $user->role === 'penyidik') {
            $summaryQuery->whereHas('perkara', function ($q) use ($user) {
                $q->where('penyidik', $user->name);
            });
        }

        $summary = [
            'total_logs'    => (clone $summaryQuery)->count(),
            'total_selesai' => (clone $summaryQuery)->where('is_active', false)->count(),
            'total_aktif'   => (clone $summaryQuery)->where('is_active', true)->count(),
            'total_overdue' => (clone $summaryQuery)->where('status_urgensi', 'Overdue')->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Data log reminder berhasil dimuat.',
            'summary' => $summary,
            'data'    => $logs,
        ]);
    }
}
