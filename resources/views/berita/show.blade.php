@extends('layouts.app')

@section('title', $berita->judul)

@section('content')
    <div class="max-w-4xl mx-auto space-y-6">
        <!-- Breadcrumb & Header Bar -->
        <div class="flex items-center justify-between">
            <nav class="flex items-center gap-2 text-xs text-gray-500">
                <a href="{{ route('berita.index') }}" class="hover:text-red-700 transition">Berita & Publikasi</a>
                <span>/</span>
                <span class="text-gray-800 font-medium">Pratinjau Berita</span>
            </nav>
            <div class="flex items-center gap-2">
                <a href="{{ route('berita.index') }}"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition shadow-xs">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali
                </a>
                <a href="{{ route('berita.edit', $berita->id) }}"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-sm font-semibold text-white bg-[#881337] hover:bg-[#700d1a] rounded-lg transition shadow-xs">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit Berita
                </a>
            </div>
        </div>

        <!-- Article Content Card -->
        <article class="bg-white rounded-xl border border-gray-200 shadow-xs overflow-hidden">
            <!-- Banner Image if available -->
            @if($berita->gambar_url)
                <div class="w-full h-72 sm:h-96 bg-gray-100 overflow-hidden relative">
                    <img src="{{ $berita->gambar_url }}" alt="{{ $berita->judul }}" class="w-full h-full object-cover">
                    @if(!$berita->is_published)
                        <div
                            class="absolute top-4 left-4 bg-amber-500 text-white text-xs font-bold px-3 py-1 rounded-full shadow-md uppercase tracking-wider">
                            Draf (Belum Publik)
                        </div>
                    @endif
                </div>
            @endif

            <div class="p-6 sm:p-8 space-y-6">
                <!-- Header Meta -->
                <div>
                    <div class="flex flex-wrap items-center gap-3 text-xs text-gray-500 mb-2">
                        <span
                            class="inline-flex items-center gap-1.5 font-medium text-red-700 bg-red-50 px-2.5 py-1 rounded-md border border-red-100">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            {{ \Carbon\Carbon::parse($berita->tanggal)->translatedFormat('l, d F Y') }}
                        </span>
                        <span>•</span>
                        <span>Penulis: <strong
                                class="text-gray-700">{{ $berita->penulis ?? 'Humas Kejaksaan' }}</strong></span>
                        <span>•</span>
                        <span>Dilihat: <strong class="text-gray-700">{{ $berita->views_count }} kali</strong></span>
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight leading-snug">
                        {{ $berita->judul }}
                    </h1>
                </div>

                <!-- Excerpt / Ringkasan -->
                @if($berita->ringkasan)
                    <div
                        class="p-4 bg-gray-50 rounded-lg border-l-4 border-[#881337] text-gray-700 italic text-sm leading-relaxed">
                        {{ $berita->ringkasan }}
                    </div>
                @endif

                <!-- Body / Konten Berita -->
                <div class="prose max-w-none text-gray-800 text-base leading-relaxed space-y-4 whitespace-pre-line">
                    @if($berita->konten)
                        {{ $berita->konten }}
                    @else
                        <p class="text-gray-400 italic">Belum ada rincian konten berita yang dimasukkan.</p>
                    @endif
                </div>


            </div>
        </article>
    </div>
@endsection