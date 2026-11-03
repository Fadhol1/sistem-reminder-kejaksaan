@extends('layouts.app')

@section('title', 'Tambah Berita Baru')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb & Header -->
    <div class="flex items-center justify-between">
        <div>
            <nav class="flex items-center gap-2 text-xs text-gray-500 mb-1">
                <a href="{{ route('berita.index') }}" class="hover:text-red-700 transition">Berita & Publikasi</a>
                <span>/</span>
                <span class="text-gray-800 font-medium">Tambah Baru</span>
            </nav>
            <h3 class="text-xl font-bold text-gray-900">Tambah Berita Baru</h3>
            <p class="text-sm text-gray-500 mt-0.5">Unggah berita, pengumuman, atau siaran pers untuk aplikasi dan website.</p>
        </div>
        <a href="{{ route('berita.index') }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition shadow-xs">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali
        </a>
    </div>

    <!-- Error Summary -->
    @if ($errors->any())
        <div class="p-4 rounded-xl bg-red-50 border border-red-200">
            <div class="flex gap-3">
                <svg class="h-5 w-5 text-red-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <h4 class="text-sm font-semibold text-red-800">Terdapat kesalahan pengisian formulir:</h4>
                    <ul class="list-disc list-inside text-xs text-red-700 mt-1 space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Form Card -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
        <form action="{{ route('berita.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            @csrf

            <!-- Judul Berita -->
            <div>
                <label for="judul" class="block text-sm font-semibold text-gray-800">
                    Judul Berita <span class="text-red-500">*</span>
                </label>
                <input type="text" name="judul" id="judul" value="{{ old('judul') }}" required placeholder="Contoh: Sosialisasi Penghentian Penuntutan Keadilan Restoratif di Wilayah Hukum Kejari"
                    class="mt-1.5 block w-full rounded-lg border @error('judul') border-red-500 @else border-gray-300 @enderror py-2.5 px-3.5 text-sm focus-visible:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20">
                @error('judul')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Grid: Tanggal & Penulis -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Tanggal -->
                <div>
                    <label for="tanggal" class="block text-sm font-semibold text-gray-800">
                        Tanggal Berita <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="tanggal" id="tanggal" value="{{ old('tanggal', date('Y-m-d')) }}" required
                        class="mt-1.5 block w-full rounded-lg border @error('tanggal') border-red-500 @else border-gray-300 @enderror py-2 px-3 text-sm focus-visible:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20">
                    @error('tanggal')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Penulis / Sumber -->
                <div>
                    <label for="penulis" class="block text-sm font-semibold text-gray-800">
                        Penulis / Sumber Rilis
                    </label>
                    <input type="text" name="penulis" id="penulis" value="{{ old('penulis', auth()->user()->name) }}" placeholder="Contoh: Seksi Tindak Pidana Umum"
                        class="mt-1.5 block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm focus-visible:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20">
                </div>
            </div>

            <!-- Upload Gambar Berita -->
            <div>
                <label class="block text-sm font-semibold text-gray-800">
                    Gambar / Banner Berita <span class="text-gray-400 font-normal">(Format JPG, PNG, WEBP max 5MB)</span>
                </label>
                <div class="mt-2 flex flex-col sm:flex-row items-center gap-4">
                    <!-- Image Preview Container -->
                    <div id="imagePreviewContainer" class="w-full sm:w-48 h-32 rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 flex items-center justify-center overflow-hidden shrink-0 relative">
                        <img id="imagePreview" src="" alt="Pratinjau Gambar" class="w-full h-full object-cover hidden">
                        <div id="previewPlaceholder" class="text-center p-3 text-gray-400">
                            <svg class="mx-auto h-8 w-8 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <span class="text-[11px]">Pratinjau Gambar</span>
                        </div>
                    </div>

                    <div class="flex-1 w-full">
                        <input type="file" name="gambar" id="gambar" accept="image/*" onchange="previewSelectedImage(this)"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100 transition cursor-pointer">
                        <p class="text-xs text-gray-500 mt-1.5">Gambar ini akan otomatis disajikan ke aplikasi Android sebagai header kartu berita.</p>
                        @error('gambar')
                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Ringkasan Singkat -->
            <div>
                <label for="ringkasan" class="block text-sm font-semibold text-gray-800">
                    Ringkasan Singkat <span class="text-gray-400 font-normal">(Deskripsi singkat untuk kartu mobile, opsional)</span>
                </label>
                <textarea name="ringkasan" id="ringkasan" rows="2" placeholder="Tuliskan ringkasan 1-2 kalimat untuk pratinjau di aplikasi Android..."
                    class="mt-1.5 block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm focus-visible:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20">{{ old('ringkasan') }}</textarea>
            </div>

            <!-- Isi Lengkap Konten Berita -->
            <div>
                <label for="konten" class="block text-sm font-semibold text-gray-800">
                    Isi Konten Berita
                </label>
                <textarea name="konten" id="konten" rows="8" placeholder="Tuliskan naskah berita lengkap di sini..."
                    class="mt-1.5 block w-full rounded-lg border border-gray-300 py-2.5 px-3.5 text-sm focus-visible:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20">{{ old('konten') }}</textarea>
            </div>

            <!-- Status Publikasi -->
            <div class="pt-2 border-t border-gray-100">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="is_published" value="0">
                    <input type="checkbox" name="is_published" value="1" {{ old('is_published', '1') == '1' ? 'checked' : '' }}
                        class="w-4 h-4 text-red-600 rounded border-gray-300 focus:ring-red-500">
                    <span class="text-sm font-medium text-gray-800">Publikasikan langsung ke aplikasi mobile dan publik</span>
                </label>
            </div>

            <!-- Tombol Aksi -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                <a href="{{ route('berita.index') }}" class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Batal
                </a>
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-[#881337] via-[#991b1b] to-[#7f1d1d] hover:from-[#700d1a] hover:via-[#881337] rounded-lg shadow-sm transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Simpan & Terbitkan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function previewSelectedImage(input) {
    const preview = document.getElementById('imagePreview');
    const placeholder = document.getElementById('previewPlaceholder');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            if (placeholder) placeholder.classList.add('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.src = '';
        preview.classList.add('hidden');
        if (placeholder) placeholder.classList.remove('hidden');
    }
}
</script>
@endsection

