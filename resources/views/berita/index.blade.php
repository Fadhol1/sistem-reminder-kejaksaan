@extends('layouts.app')

@section('title', 'Manajemen Berita & Publikasi')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="text-xl font-semibold text-gray-900">Manajemen Berita & Publikasi</h3>
                <p class="text-sm text-gray-500 mt-1">Kelola berita, pengumuman, dan siaran informasi untuk website dan
                    aplikasi mobile.</p>
            </div>
            @if(auth()->user()->isPidum())
                <a href="{{ route('berita.create') }}"
                    class="inline-flex items-center justify-center rounded-lg bg-gradient-to-r from-[#881337] via-[#991b1b] to-[#7f1d1d] hover:from-[#700d1a] hover:via-[#881337] hover:to-[#5c0b15] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition w-full sm:w-auto">
                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Tambah Berita Baru
                </a>
            @endif
        </div>

        <!-- Alert Notifications -->
        @if(session('success'))
            <div class="p-4 rounded-lg bg-emerald-50 border border-emerald-200">
                <div class="flex items-center gap-3">
                    <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        <!-- Search & Filter Card -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-xs">
            <form action="{{ route('berita.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" name="search" value="{{ request('search') }}"
                        placeholder="Cari judul berita atau penulis..."
                        class="block w-full pl-9 rounded-lg border border-gray-300 py-2 px-3 text-sm focus-visible:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20">
                </div>
                <div class="sm:w-48">
                    <select name="status"
                        class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm focus-visible:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20">
                        <option value="">Semua Status</option>
                        <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Dipublikasikan
                        </option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draf</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg transition">Filter</button>
                    @if(request()->hasAny(['search', 'status']))
                        <a href="{{ route('berita.index') }}"
                            class="px-4 py-2 border border-gray-200 hover:bg-gray-50 text-gray-600 text-sm font-medium rounded-lg transition">Reset</a>
                    @endif
                </div>
            </form>
        </div>

        <!-- News Table -->
        <div class="bg-white border border-gray-200 rounded-xl shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50/75 text-gray-600 uppercase text-xs font-semibold">
                        <tr>
                            <th scope="col" class="px-6 py-3.5">Gambar</th>
                            <th scope="col" class="px-6 py-3.5">Judul & Ringkasan</th>
                            <th scope="col" class="px-6 py-3.5">Tanggal</th>
                            <th scope="col" class="px-6 py-3.5">Penulis</th>
                            <th scope="col" class="px-6 py-3.5">Status</th>
                            <th scope="col" class="px-6 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($beritas as $item)
                            <tr class="hover:bg-gray-50/70 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($item->gambar_url)
                                        <img src="{{ $item->gambar_url }}" alt="{{ $item->judul }}"
                                            class="w-16 h-12 object-cover rounded-lg border border-gray-200 shadow-xs">
                                    @else
                                        <div
                                            class="w-16 h-12 rounded-lg bg-gray-100 border border-gray-200 flex items-center justify-center text-gray-400">
                                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 max-w-md">
                                    <a href="{{ route('berita.show', $item->id) }}"
                                        class="font-semibold text-gray-900 hover:text-red-700 transition line-clamp-1">
                                        {{ $item->judul }}
                                    </a>
                                    <p class="text-xs text-gray-500 mt-1 line-clamp-2">
                                        {{ $item->ringkasan ?? Str::limit(strip_tags($item->konten), 90) }}
                                    </p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $item->penulis ?? 'Admin' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($item->is_published)
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Publik
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                                            Draf
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('berita.show', $item->id) }}"
                                            class="p-1.5 text-gray-500 hover:text-gray-800 hover:bg-gray-100 rounded-lg transition"
                                            title="Lihat">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </a>
                                        @if(auth()->user()->isPidum())
                                            <a href="{{ route('berita.edit', $item->id) }}"
                                                class="p-1.5 text-indigo-600 hover:text-indigo-900 hover:bg-indigo-50 rounded-lg transition"
                                                title="Edit">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </a>
                                            <form action="{{ route('berita.destroy', $item->id) }}" method="POST"
                                                onsubmit="return confirm('Apakah Anda yakin ingin menghapus berita ini?')"
                                                class="inline-block">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                    class="p-1.5 text-red-600 hover:text-red-900 hover:bg-red-50 rounded-lg transition"
                                                    title="Hapus">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                            d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                                    </svg>
                                    <p class="text-base font-medium text-gray-900">Belum ada berita yang tersedia</p>
                                    <p class="text-sm text-gray-500 mt-1">Mulai publikasikan berita atau siaran pers pertama
                                        Anda.</p>
                                    <div class="mt-4">
                                        <a href="{{ route('berita.create') }}"
                                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-[#881337] hover:bg-[#700d1a]">
                                            Tambah Berita
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($beritas->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50/50">
                    {{ $beritas->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection