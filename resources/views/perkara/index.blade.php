@extends('layouts.app')

@section('title', 'Daftar Perkara (PIDUM)')

@section('content')
<div class="space-y-6 w-full mx-auto">
    <!-- Header Page -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2 border-b border-gray-200">
        <div>
            <div class="flex items-center gap-2">
                <span class="p-2 rounded-lg bg-indigo-50 text-indigo-600 border border-indigo-100">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                </span>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Daftar Perkara (PIDUM)</h2>
                    <p class="text-sm text-gray-500">Kelola berkas, pantau mutasi tahapan, dan awasi kepatuhan batas waktu perkara.</p>
                </div>
            </div>
        </div>

        @if(auth()->user()->isPidum())
            <div>
                <a href="{{ route('perkara.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    <span>Tambah Perkara Baru</span>
                </a>
            </div>
        @endif
    </div>

    <!-- Alert Success -->
    @if(session('success'))
        <div class="p-4 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-between" role="alert">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <!-- 4 Stats / KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Total Perkara -->
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Perkara</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['total_perkara'] ?? 0) }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">Semua data terdaftar</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-gray-50 text-gray-700 border border-gray-200 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />
                </svg>
            </div>
        </div>

        <!-- Card 2: Perkara Aktif -->
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider">Perkara Berjalan</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['perkara_aktif'] ?? 0) }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">Sedang dalam penanganan</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 border border-blue-200 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                </svg>
            </div>
        </div>

        <!-- Card 3: Perkara Lewat SLA / Overdue -->
        <div class="bg-white p-5 rounded-xl border {{ ($stats['ada_overdue'] ?? 0) > 0 ? 'border-red-300 bg-red-50/20' : 'border-gray-200' }} shadow-sm flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5">
                    <p class="text-xs font-semibold text-red-600 uppercase tracking-wider">SLA Overdue</p>
                    @if(($stats['ada_overdue'] ?? 0) > 0)
                        <span class="inline-flex items-center px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-red-100 text-red-700 animate-pulse">Butuh Aksi</span>
                    @endif
                </div>
                <h3 class="text-2xl font-bold text-red-600 mt-1">{{ number_format($stats['ada_overdue'] ?? 0) }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">Melewati batas tenggat waktu</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 border border-red-200 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
        </div>

        <!-- Card 4: Tahap Selesai -->
        <div class="bg-white p-5 rounded-xl border border-gray-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Perkara Selesai</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-1">{{ number_format($stats['tahap_selesai'] ?? 0) }}</h3>
                <p class="text-xs text-gray-400 mt-0.5">Tahap II & SPDP Dikembalikan</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center shrink-0">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Search/Filter Controls -->
    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm">
        <form action="{{ route('perkara.index') }}" method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                <!-- Search Input -->
                <div class="md:col-span-5 relative">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Cari Keyword / Perkara</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" 
                            placeholder="Nomor SPDP, nama tersangka, jaksa, atau penyidik..." 
                            class="block w-full rounded-lg border border-gray-300 py-2 pl-9 pr-3 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                </div>

                <!-- Tahapan Dropdown -->
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tahap Perkara</label>
                    <select name="tahap" class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="">Semua Tahap</option>
                        @foreach($tahapList as $tahap)
                            <option value="{{ $tahap }}" {{ request('tahap') == $tahap ? 'selected' : '' }}>
                                {{ $tahap }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Status Filter Dropdown -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Status Penanganan</label>
                    <select name="status_filter" class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="">Semua Status</option>
                        <option value="aktif" {{ request('status_filter') == 'aktif' ? 'selected' : '' }}>Perkara Berjalan</option>
                        <option value="selesai" {{ request('status_filter') == 'selesai' ? 'selected' : '' }}>Perkara Selesai</option>
                    </select>
                </div>

                <!-- Submit & Reset Buttons -->
                <div class="md:col-span-2 flex items-end gap-2">
                    <button type="submit" class="flex-1 inline-flex items-center justify-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800 transition">
                        Saring
                    </button>
                    @if(request()->hasAny(['search', 'tahap', 'status_filter']))
                        <a href="{{ route('perkara.index') }}" title="Reset Filter" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Table Listing -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left align-middle">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50/80 border-b border-gray-200 font-semibold tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">No. Perkara / SPDP</th>
                        <th class="px-6 py-3.5">Tersangka</th>
                        <th class="px-6 py-3.5">Jaksa & Penyidik</th>
                        <th class="px-6 py-3.5">Tahap Saat Ini</th>
                        <th class="px-6 py-3.5">Status Kegiatan</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($perkaras as $perkara)
                        @php
                            $tahapBg = match($perkara->tahap_saat_ini) {
                                'Koordinasi Awal' => 'bg-blue-50 text-blue-700 border-blue-200',
                                'Monitoring Penyidikan' => 'bg-amber-50 text-amber-700 border-amber-200',
                                'P-24' => 'bg-purple-50 text-purple-700 border-purple-200',
                                'P-19' => 'bg-orange-50 text-orange-700 border-orange-200',
                                'P-21' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'Tahap II' => 'bg-teal-50 text-teal-700 border-teal-200',
                                default => 'bg-gray-100 text-gray-700 border-gray-200',
                            };

                            $hasOverdue = $perkara->activeReminders->contains('status_urgensi', 'Overdue');
                            $hasJatuhTempo = $perkara->activeReminders->contains('status_urgensi', 'Jatuh Tempo');
                        @endphp
                        <tr class="hover:bg-gray-50/60 transition-colors">
                            <!-- Kolom No. Perkara -->
                            <td class="px-6 py-4">
                                <a href="{{ route('perkara.show', $perkara->id) }}" class="font-bold text-gray-900 hover:text-indigo-600 hover:underline inline-flex items-center gap-1.5">
                                    {{ $perkara->nomor_perkara }}
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                                <div class="text-xs text-gray-400 mt-0.5">
                                    SPDP: {{ \Carbon\Carbon::parse($perkara->tanggal_spdp)->translatedFormat('d M Y') }}
                                </div>
                            </td>

                            <!-- Kolom Tersangka -->
                            <td class="px-6 py-4">
                                @php
                                    $tersangkaList = $perkara->tersangkas;
                                    $firstNama = $tersangkaList->first()?->nama ?? '-';
                                    $sisa = $tersangkaList->count() - 1;
                                @endphp
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-gray-900">{{ $firstNama }}</span>
                                    @if($sisa > 0)
                                        <button type="button" onclick="toggleTersangka('tersangka-{{ $perkara->id }}')" 
                                            class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[11px] font-semibold bg-indigo-50 text-indigo-700 hover:bg-indigo-100 transition">
                                            +{{ $sisa }} lainnya
                                            <svg class="w-3 h-3 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>

                            <!-- Kolom Jaksa & Penyidik -->
                            <td class="px-6 py-4">
                                <div class="text-xs font-semibold text-gray-900 flex items-center gap-1">
                                    <span class="text-gray-400 font-normal">Jaksa:</span>
                                    <span>
                                        @if($perkara->jaksas && $perkara->jaksas->count() > 0)
                                            {{ $perkara->jaksas->pluck('name')->join(', ') }}
                                        @else
                                            {{ $perkara->jaksa ?? '-' }}
                                        @endif
                                    </span>
                                </div>
                                <div class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                                    <span class="text-gray-400">Penyidik:</span>
                                    <span>{{ $perkara->penyidik ?? '-' }}</span>
                                </div>
                            </td>

                            <!-- Kolom Tahap -->
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider border {{ $tahapBg }}">
                                    {{ $perkara->tahap_saat_ini }}
                                </span>
                            </td>

                            <!-- Kolom Status & Urgensi SLA -->
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 text-xs sm:text-sm">
                                    {{ $perkara->status_saat_ini }}
                                </div>
                                <div class="mt-1 flex items-center gap-1.5">
                                    @if($hasOverdue)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700 border border-red-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-600 animate-pulse"></span>
                                            SLA Overdue
                                        </span>
                                    @elseif($hasJatuhTempo)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 border border-rose-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-600"></span>
                                            Jatuh Tempo
                                        </span>
                                    @elseif($perkara->status_saat_ini === 'Tahap II Selesai')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            ✓ Tahap II Selesai
                                        </span>
                                    @elseif(str_contains($perkara->status_saat_ini, 'FORM-3'))
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-300">
                                            ✓ SPDP Dikembalikan (Arsip)
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Kolom Aksi -->
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('perkara.show', $perkara->id) }}" 
                                   class="inline-flex items-center gap-1.5 rounded-lg text-xs font-semibold bg-white border border-gray-300 shadow-2xs hover:bg-gray-50 hover:border-gray-400 py-1.5 px-3 transition text-gray-700">
                                    <span>Detail</span>
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </td>
                        </tr>

                        <!-- Accordion Row for Extended Suspects List -->
                        @if($tersangkaList && $tersangkaList->count() > 1)
                            <tr id="tersangka-{{ $perkara->id }}" class="hidden bg-gray-50/70">
                                <td class="px-6 py-2.5"></td>
                                <td colspan="5" class="px-6 py-2.5">
                                    <div class="text-xs text-gray-600 font-medium mb-1">Daftar Lengkap Tersangka:</div>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($tersangkaList as $tersangka)
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs bg-white text-gray-800 border border-gray-200 shadow-2xs">
                                                <span class="font-bold text-indigo-600">{{ $loop->iteration }}.</span>
                                                {{ $tersangka->nama }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-14 text-center">
                                <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 flex items-center justify-center text-gray-400">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                    </svg>
                                </div>
                                <h4 class="text-sm font-semibold text-gray-900">Tidak ada perkara yang ditemukan</h4>
                                <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">
                                    Belum ada data perkara terdaftar atau filter pencarian Anda tidak memiliki kecocokan data.
                                </p>
                                @if(request()->hasAny(['search', 'tahap', 'status_filter']))
                                    <div class="mt-3">
                                        <a href="{{ route('perkara.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                            Reset Filter Pencarian
                                        </a>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar (10 data per halaman) -->
        @if($perkaras->hasPages())
            <div class="bg-white px-5 py-3.5 border-t border-gray-200">
                {{ $perkaras->links() }}
            </div>
        @endif
    </div>
</div>

<script>
    function toggleTersangka(id) {
        const el = document.getElementById(id);
        if (el) el.classList.toggle('hidden');
    }
</script>
@endsection