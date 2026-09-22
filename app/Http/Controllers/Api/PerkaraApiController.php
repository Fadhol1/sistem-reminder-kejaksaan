<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Perkara;
use App\Models\PerkaraReminder;
use App\Models\PerkaraTimeline;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PerkaraApiController extends Controller
{
    /**
     * 1. INDEX: Mengembalikan daftar semua perkara dalam format JSON,
     * lengkap dengan relasi timeline terakhir dan reminder aktif.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Perkara::with(['latestTimeline', 'activeReminders']);

        // Filter pencarian berdasarkan nomor perkara atau nama tersangka
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nomor_perkara', 'like', "%{$search}%")
                  ->orWhere('nama_tersangka', 'like', "%{$search}%");
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

        // Filter berdasarkan Jaksa atau Penyidik
        if ($request->filled('jaksa')) {
            $query->where('jaksa', 'like', "%{$request->input('jaksa')}%");
        }
        if ($request->filled('penyidik')) {
            $query->where('penyidik', 'like', "%{$request->input('penyidik')}%");
        }

        $query->orderBy('created_at', 'desc');

        // Dukungan pagination opsional atau ambil seluruh data
        if ($request->boolean('paginate', false) || $request->has('per_page')) {
            $perPage = (int) $request->input('per_page', 15);
            $perkaras = $query->paginate($perPage);
        } else {
            $perkaras = $query->get();
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar perkara berhasil dimuat.',
            'count'   => is_countable($perkaras) ? count($perkaras) : $perkaras->total(),
            'data'    => $perkaras,
        ]);
    }

    /**
     * 2. SHOW: Mengembalikan detail lengkap satu perkara berdasarkan ID
     * beserta seluruh riwayat timeline dan reminder-nya.
     */
    public function show(int|string $id): JsonResponse
    {
        $perkara = Perkara::with([
            'timelines' => function ($q) {
                $q->orderBy('tanggal_kejadian', 'desc')->orderBy('id', 'desc');
            },
            'reminders' => function ($q) {
                $q->orderBy('deadline', 'asc')->orderBy('is_active', 'desc');
            },
            'activeReminders',
        ])->find($id);

        if (! $perkara) {
            return response()->json([
                'success' => false,
                'message' => "Data perkara dengan ID {$id} tidak ditemukan.",
                'data'    => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail perkara berhasil dimuat.',
            'data'    => $perkara,
        ]);
    }

    /**
     * 3. STORE: Menyimpan data perkara baru dengan validasi lengkap,
     * serta otomatis menginisialisasi timeline awal dan reminder SLA (opsional/default).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nomor_perkara'   => 'required|string|max:255|unique:perkaras,nomor_perkara',
            'nama_tersangka'  => 'required|string|max:255',
            'penyidik'        => 'required|string|max:255',
            'jaksa'           => 'required|string|max:255',
            'tanggal_spdp'    => 'required|date',
            'tahap_saat_ini'  => 'required|string|max:255',
            'status_saat_ini' => 'required|string|max:255',

            // Input tambahan opsional untuk catatan awal & reminder SLA
            'catatan'         => 'nullable|string',
            'buat_reminder'   => 'nullable|boolean',
            'jenis_reminder'  => 'nullable|string|max:255',
            'deadline_days'   => 'nullable|integer|min:1',
            'deadline'        => 'nullable|date|after_or_equal:tanggal_spdp',
        ], [
            'nomor_perkara.required' => 'Nomor perkara wajib diisi.',
            'nomor_perkara.unique'   => 'Nomor perkara sudah terdaftar di sistem.',
            'nama_tersangka.required'=> 'Nama tersangka wajib diisi.',
            'penyidik.required'      => 'Nama penyidik wajib diisi.',
            'jaksa.required'         => 'Nama jaksa wajib diisi.',
            'tanggal_spdp.required'  => 'Tanggal SPDP wajib diisi.',
            'tahap_saat_ini.required'=> 'Tahap saat ini wajib diisi.',
            'status_saat_ini.required'=> 'Status saat ini wajib diisi.',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            // 1. Simpan perkara utama
            $perkara = Perkara::create([
                'nomor_perkara'   => $validated['nomor_perkara'],
                'nama_tersangka'  => $validated['nama_tersangka'],
                'penyidik'        => $validated['penyidik'],
                'jaksa'           => $validated['jaksa'],
                'tanggal_spdp'    => $validated['tanggal_spdp'],
                'tahap_saat_ini'  => $validated['tahap_saat_ini'],
                'status_saat_ini' => $validated['status_saat_ini'],
            ]);

            // 2. Buat entri timeline perdana
            $perkara->timelines()->create([
                'tahap'            => $perkara->tahap_saat_ini,
                'status_kegiatan'  => $perkara->status_saat_ini,
                'tanggal_kejadian' => $perkara->tanggal_spdp,
                'catatan'          => $validated['catatan'] ?? 'Penerimaan berkas SPDP awal.',
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
                    'tanggal_mulai'  => $perkara->tanggal_spdp,
                    'deadline'       => $deadlineDate->toDateString(),
                    'status_urgensi' => PerkaraReminder::determineUrgensi($deadlineDate),
                    'is_active'      => true,
                ]);
            }

            // Muat relasi lengkap untuk response
            $perkara->load(['latestTimeline', 'activeReminders']);

            return response()->json([
                'success' => true,
                'message' => 'Data perkara baru berhasil disimpan dan diinisialisasi.',
                'data'    => $perkara,
            ], 201);
        });
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
            'perkara_id'               => 'required|exists:perkaras,id',
            'tahap'                    => 'required|string|max:255',
            'status_kegiatan'          => 'required|string|max:255',
            'tanggal_kejadian'         => 'required|date',
            'catatan'                  => 'nullable|string',

            // Update status perkara opsional (default menggunakan tahap dan status_kegiatan baru)
            'update_status_perkara'    => 'nullable|boolean',
            'tahap_saat_ini'           => 'nullable|string|max:255',
            'status_saat_ini'          => 'nullable|string|max:255',

            // Manajemen reminder baru / pengganti
            'create_reminder'          => 'nullable|boolean',
            'close_previous_reminders' => 'nullable|boolean', // Tutup reminder aktif sebelumnya
            'jenis_reminder'           => 'nullable|string|max:255',
            'tanggal_mulai'            => 'nullable|date',
            'deadline'                 => 'nullable|date',
            'status_urgensi'           => ['nullable', Rule::in(['Aman', 'H-3', 'Jatuh Tempo', 'Overdue'])],
            'is_active'                => 'nullable|boolean',
        ], [
            'perkara_id.required'       => 'ID Perkara wajib disertakan.',
            'perkara_id.exists'         => 'Data perkara yang dipilih tidak ditemukan.',
            'tahap.required'            => 'Tahap perkembangan perkara wajib diisi.',
            'status_kegiatan.required'   => 'Status kegiatan wajib diisi.',
            'tanggal_kejadian.required' => 'Tanggal kejadian wajib diisi.',
        ]);

        return DB::transaction(function () use ($validated, $request, $perkaraId) {
            $perkara = Perkara::findOrFail($perkaraId);

            // 1. Simpan riwayat perkembangan ke perkara_timelines
            $timeline = $perkara->timelines()->create([
                'tahap'            => $validated['tahap'],
                'status_kegiatan'  => $validated['status_kegiatan'],
                'tanggal_kejadian' => $validated['tanggal_kejadian'],
                'catatan'          => $validated['catatan'] ?? null,
            ]);

            // 2. Perbarui tahap_saat_ini dan status_saat_ini pada tabel perkaras
            $updateStatusPerkara = $request->boolean('update_status_perkara', true);
            if ($updateStatusPerkara) {
                $perkara->update([
                    'tahap_saat_ini'  => $validated['tahap_saat_ini'] ?? $validated['tahap'],
                    'status_saat_ini' => $validated['status_saat_ini'] ?? $validated['status_kegiatan'],
                ]);
            }

            // 3. Kelola reminder terkait:
            // Nonaktifkan reminder aktif sebelumnya jika diinstruksikan atau jika ada reminder baru yang menggantikan
            $hasNewReminderData = $request->boolean('create_reminder') || ! empty($validated['jenis_reminder']);
            $shouldClosePrevious = $request->boolean('close_previous_reminders', $hasNewReminderData);

            if ($shouldClosePrevious) {
                $perkara->reminders()->where('is_active', true)->update([
                    'is_active' => false,
                ]);
            }

            // Buat reminder baru jika data reminder disediakan
            $newReminder = null;
            if ($hasNewReminderData && ! empty($validated['jenis_reminder'])) {
                $tglMulai = $validated['tanggal_mulai'] ?? $validated['tanggal_kejadian'];
                $deadline = $validated['deadline'] ?? Carbon::parse($tglMulai)->addDays(14)->toDateString();
                $urgensi = $validated['status_urgensi'] ?? PerkaraReminder::determineUrgensi($deadline);

                $newReminder = $perkara->reminders()->create([
                    'jenis_reminder' => $validated['jenis_reminder'],
                    'tanggal_mulai'  => $tglMulai,
                    'deadline'       => $deadline,
                    'status_urgensi' => $urgensi,
                    'is_active'      => $request->boolean('is_active', true),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Perkembangan perkara berhasil dicatat, status perkara diperbarui, dan reminder dikelola.',
                'data'    => [
                    'timeline'     => $timeline,
                    'perkara'      => $perkara->fresh(['latestTimeline', 'activeReminders']),
                    'new_reminder' => $newReminder,
                ],
            ], 201);
        });
    }

    /**
     * 5. GET REMINDERS: Mengembalikan daftar reminder khusus yang berstatus aktif
     * atau mendesak (Aman, H-3, Jatuh Tempo, Overdue) untuk keperluan notifikasi
     * di dashboard Android kepala.
     */
    public function getReminders(Request $request): JsonResponse
    {
        // Query builder untuk reminder
        $query = PerkaraReminder::with([
            'perkara' => function ($q) {
                $q->select('id', 'nomor_perkara', 'nama_tersangka', 'penyidik', 'jaksa', 'tahap_saat_ini', 'status_saat_ini');
            },
        ]);

        // Filter aktif (default true, kecuali jika eksplisit meminta 'all' atau 'false')
        if ($request->input('is_active') !== 'all') {
            $isActive = $request->boolean('is_active', true);
            $query->where('is_active', $isActive);
        }

        // Filter status urgensi tertentu jika diberikan (contoh: status_urgensi=Overdue atau status_urgensi=H-3,Jatuh Tempo)
        if ($request->filled('status_urgensi')) {
            $urgensiList = explode(',', $request->input('status_urgensi'));
            $query->whereIn('status_urgensi', array_map('trim', $urgensiList));
        }

        // Filter hanya yang mendesak (H-3, Jatuh Tempo, Overdue)
        if ($request->boolean('urgent_only', false)) {
            $query->urgent();
        }

        // Filter pencarian perkara (nomor perkara, nama tersangka, jaksa)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->whereHas('perkara', function ($q) use ($search) {
                $q->where('nomor_perkara', 'like', "%{$search}%")
                  ->orWhere('nama_tersangka', 'like', "%{$search}%")
                  ->orWhere('jaksa', 'like', "%{$search}%");
            });
        }

        // Urutkan prioritas urgensi: Overdue (tertinggi) -> Jatuh Tempo -> H-3 -> Aman, lalu berdasarkan deadline terdekat
        $query->orderByRaw("
            CASE status_urgensi
                WHEN 'Overdue' THEN 1
                WHEN 'Jatuh Tempo' THEN 2
                WHEN 'H-3' THEN 3
                WHEN 'Aman' THEN 4
                ELSE 5
            END ASC
        ")->orderBy('deadline', 'asc');

        $reminders = $query->get();

        // Rekapitulasi ringkas statistik dashboard Android untuk badge notifikasi
        $summary = [
            'total_active' => PerkaraReminder::where('is_active', true)->count(),
            'total_urgent' => PerkaraReminder::where('is_active', true)->whereIn('status_urgensi', ['H-3', 'Jatuh Tempo', 'Overdue'])->count(),
            'overdue'      => PerkaraReminder::where('is_active', true)->where('status_urgensi', 'Overdue')->count(),
            'jatuh_tempo'  => PerkaraReminder::where('is_active', true)->where('status_urgensi', 'Jatuh Tempo')->count(),
            'h_min_3'      => PerkaraReminder::where('is_active', true)->where('status_urgensi', 'H-3')->count(),
            'aman'         => PerkaraReminder::where('is_active', true)->where('status_urgensi', 'Aman')->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Daftar reminder berhasil dimuat.',
            'summary' => $summary,
            'count'   => $reminders->count(),
            'data'    => $reminders,
        ]);
    }
}
