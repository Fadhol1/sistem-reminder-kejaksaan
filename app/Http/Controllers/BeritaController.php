<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BeritaController extends Controller
{
    /**
     * Menampilkan daftar berita pada website admin.
     */
    public function index(Request $request)
    {
        $query = Berita::query()->latest('tanggal')->latest('id');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('penulis', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $isPublished = $request->input('status') === 'published';
            $query->where('is_published', $isPublished);
        }

        $beritas = $query->paginate(10)->withQueryString();

        return view('berita.index', compact('beritas'));
    }

    /**
     * Form tambah berita baru.
     */
    public function create()
    {
        abort_unless(auth()->user()->isPidum(), 403, 'Akses Ditolak: Hanya Admin PIDUM yang dapat menambah berita.');
        return view('berita.create');
    }

    /**
     * Simpan berita baru ke database dan upload gambar.
     */
    public function store(Request $request)
    {
        abort_unless(auth()->user()->isPidum(), 403, 'Akses Ditolak: Hanya Admin PIDUM yang dapat menambah berita.');

        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'tanggal' => ['required', 'date'],
            'gambar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:5120'],
            'ringkasan' => ['nullable', 'string', 'max:1000'],
            'konten' => ['nullable', 'string'],
            'penulis' => ['nullable', 'string', 'max:150'],
            'is_published' => ['nullable', 'boolean'],
        ], [
            'judul.required' => 'Judul berita wajib diisi.',
            'tanggal.required' => 'Tanggal berita wajib ditentukan.',
            'gambar.image' => 'Berkas gambar harus berupa format gambar (jpg, png, webp).',
            'gambar.max' => 'Ukuran gambar maksimal 5 MB.',
        ]);

        $data = [
            'judul' => $validated['judul'],
            'tanggal' => $validated['tanggal'],
            'ringkasan' => $validated['ringkasan'] ?? (isset($validated['konten']) ? Str::limit(strip_tags($validated['konten']), 160) : null),
            'konten' => $validated['konten'] ?? null,
            'penulis' => $validated['penulis'] ?? auth()->user()->name,
            'user_id' => auth()->id(),
            'is_published' => $request->boolean('is_published', true),
        ];

        if ($request->hasFile('gambar')) {
            $data['gambar'] = $request->file('gambar')->store('berita', 'public');
        }

        Berita::create($data);

        return redirect()->route('berita.index')->with('success', 'Berita berhasil diterbitkan.');
    }

    /**
     * Menampilkan detail preview berita.
     */
    public function show($id)
    {
        $berita = Berita::findOrFail($id);
        return view('berita.show', compact('berita'));
    }

    /**
     * Form edit berita.
     */
    public function edit($id)
    {
        abort_unless(auth()->user()->isPidum(), 403, 'Akses Ditolak: Hanya Admin PIDUM yang dapat mengedit berita.');
        $berita = Berita::findOrFail($id);
        return view('berita.edit', compact('berita'));
    }

    /**
     * Update data berita dan gambar.
     */
    public function update(Request $request, $id)
    {
        abort_unless(auth()->user()->isPidum(), 403, 'Akses Ditolak: Hanya Admin PIDUM yang dapat mengedit berita.');
        $berita = Berita::findOrFail($id);

        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'tanggal' => ['required', 'date'],
            'gambar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp,gif', 'max:5120'],
            'ringkasan' => ['nullable', 'string', 'max:1000'],
            'konten' => ['nullable', 'string'],
            'penulis' => ['nullable', 'string', 'max:150'],
            'is_published' => ['nullable', 'boolean'],
        ], [
            'judul.required' => 'Judul berita wajib diisi.',
            'tanggal.required' => 'Tanggal berita wajib ditentukan.',
            'gambar.image' => 'Berkas gambar harus berupa format gambar (jpg, png, webp).',
            'gambar.max' => 'Ukuran gambar maksimal 5 MB.',
        ]);

        $berita->judul = $validated['judul'];
        $berita->tanggal = $validated['tanggal'];
        $berita->ringkasan = $validated['ringkasan'] ?? (isset($validated['konten']) ? Str::limit(strip_tags($validated['konten']), 160) : null);
        $berita->konten = $validated['konten'] ?? null;
        $berita->penulis = $validated['penulis'] ?? $berita->penulis;
        $berita->is_published = $request->boolean('is_published', true);

        if ($request->hasFile('gambar')) {
            // Hapus gambar lama jika ada
            if ($berita->gambar && Storage::disk('public')->exists($berita->gambar)) {
                Storage::disk('public')->delete($berita->gambar);
            }
            $berita->gambar = $request->file('gambar')->store('berita', 'public');
        }

        $berita->save();

        return redirect()->route('berita.index')->with('success', 'Data berita berhasil diperbarui.');
    }

    /**
     * Hapus berita beserta gambarnya.
     */
    public function destroy($id)
    {
        abort_unless(auth()->user()->isPidum(), 403, 'Akses Ditolak Hanya Admin PIDUM yang dapat menghapus berita.');
        $berita = Berita::findOrFail($id);

        if ($berita->gambar && Storage::disk('public')->exists($berita->gambar)) {
            Storage::disk('public')->delete($berita->gambar);
        }

        $berita->delete();

        return redirect()->route('berita.index')->with('success', 'Berita berhasil dihapus.');
    }
}

