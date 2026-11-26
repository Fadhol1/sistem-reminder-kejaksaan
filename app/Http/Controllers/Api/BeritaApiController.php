<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Berita;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BeritaApiController extends Controller
{
    /**
     * Mengambil daftar berita untuk aplikasi Android / Client.
     *
     * Parameter query opsional:
     * - search / q : pencarian judul, ringkasan, atau isi konten
     * - per_page / limit : jumlah berita per halaman (default: 10, max: 50)
     * - page : nomor halaman (default: 1)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Berita::published()->orderBy('tanggal', 'desc')->orderBy('id', 'desc');

        // Fitur pencarian berita
        $keyword = $request->input('search', $request->input('q'));
        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                $q->where('judul', 'like', "%{$keyword}%")
                  ->orWhere('ringkasan', 'like', "%{$keyword}%")
                  ->orWhere('konten', 'like', "%{$keyword}%");
            });
        }

        $perPage = (int) $request->input('per_page', $request->input('limit', 10));
        $perPage = max(1, min($perPage, 50)); // Batasi antara 1 - 50 per halaman

        $beritas = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Daftar berita berhasil diambil',
            'data'    => $beritas->items(),
            'pagination' => [
                'current_page' => $beritas->currentPage(),
                'per_page'     => $beritas->perPage(),
                'total'        => $beritas->total(),
                'last_page'    => $beritas->lastPage(),
                'has_more'     => $beritas->hasMorePages(),
            ],
        ]);
    }

    /**
     * Mengambil detail satu berita berdasarkan ID atau Slug.
     */
    public function show($id): JsonResponse
    {
        $berita = $this->findPublishedBerita($id);

        if (!$berita) {
            return response()->json([
                'success' => false,
                'message' => 'Berita tidak ditemukan',
            ], 404);
        }

        // Tambah jumlah view secara pasif
        $berita->increment('views_count');
        $berita->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Detail berita berhasil diambil',
            'data'    => $berita,
        ]);
    }

    /**
     * Mencatat penambahan jumlah dilihat (views_count) untuk endpoint POST /api/berita/{id}/view.
     */
    public function recordView($id): JsonResponse
    {
        $berita = $this->findPublishedBerita($id);

        if (!$berita) {
            return response()->json([
                'success' => false,
                'message' => 'Berita tidak ditemukan',
            ], 404);
        }

        $berita->increment('views_count');
        $berita->refresh();

        return response()->json([
            'success'     => true,
            'message'     => 'Jumlah dilihat berhasil diperbarui',
            'views_count' => $berita->views_count,
            'data'        => $berita,
        ]);
    }

    /**
     * Cari berita yang sudah dipublikasikan berdasarkan ID atau Slug.
     */
    protected function findPublishedBerita($id): ?Berita
    {
        return Berita::published()
            ->where(function ($query) use ($id) {
                if (is_numeric($id)) {
                    $query->where('id', $id)->orWhere('slug', (string) $id);
                } else {
                    $query->where('slug', $id);
                }
            })
            ->first();
    }
}

