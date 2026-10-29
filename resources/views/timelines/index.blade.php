@extends('layouts.app')

@section('title', 'Kronologis & Log Aktivitas (Timeline)')

@section('content')
<div class="space-y-6 w-full mx-auto">
    <!-- Header Page -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2 border-b border-gray-200">
        <div>
            <div class="flex items-center gap-2">
                <span class="p-2 rounded-lg bg-red-50 text-red-700 border border-red-200">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </span>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Kronologis & Log Aktivitas (Timeline)</h2>
                    <p class="text-sm text-gray-500">Audit trail pergerakan perkara, pergantian tahapan, dan eksekusi otomatis SLA.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Sinkronisasi Otomatis Real-time
            </span>
        </div>
    </div>

    <!-- 4 Stats / KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        <!-- Card 1: Total Aktivitas -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-2xs flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Catatan</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($stats['total_aktivitas'] ?? 0) }}</h3>
                <p class="text-[11px] text-gray-400 mt-0.5">Semua jejak audit trail</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-gray-50 text-gray-700 border border-gray-200 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
            </div>
        </div>

        <!-- Card 2: Hari Ini -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-2xs flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-emerald-700 uppercase tracking-wider">Aktivitas Hari Ini</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($stats['hari_ini'] ?? 0) }}</h3>
                <p class="text-[11px] text-gray-400 mt-0.5">{{ now()->translatedFormat('d F Y') }}</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-200 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
            </div>
        </div>

        <!-- Card 3: Otomasi SLA Sistem -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-2xs flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-purple-700 uppercase tracking-wider">Otomasi SLA Sistem</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($stats['oleh_sistem'] ?? 0) }}</h3>
                <p class="text-[11px] text-gray-400 mt-0.5">Auto Form-1B/1C, P-17, dll.</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-purple-50 text-purple-600 border border-purple-200 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                </svg>
            </div>
        </div>

        <!-- Card 4: Tindakan Petugas -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-2xs flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-slate-700 uppercase tracking-wider">Tindakan Petugas</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($stats['oleh_petugas'] ?? 0) }}</h3>
                <p class="text-[11px] text-gray-400 mt-0.5">Jaksa, Penyidik & Admin</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 border border-blue-200 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Filter & Search Controls -->
    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-2xs">
        <form action="{{ route('timelines.index') }}" method="GET" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                <!-- Search Keyword -->
                <div class="md:col-span-5 relative">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Cari Keyword / Perkara</label>
                    <div class="relative">
                        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                            <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" 
                            placeholder="Nomor perkara, tersangka, status, atau catatan..." 
                            class="block w-full rounded-lg border border-gray-300 py-2 pl-9 pr-3 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/20">
                    </div>
                </div>

                <!-- Tahapan Filter -->
                <div class="md:col-span-3">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tahap Perkara</label>
                    <select name="tahap" class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/20">
                        <option value="">Semua Tahap</option>
                        @foreach($tahapList as $tahap)
                            <option value="{{ $tahap }}" {{ request('tahap') == $tahap ? 'selected' : '' }}>
                                {{ $tahap }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Aktor Filter -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Pelaksana</label>
                    <select name="actor" class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/20">
                        <option value="">Semua Pelaksana</option>
                        <option value="SYSTEM" {{ request('actor') == 'SYSTEM' ? 'selected' : '' }}>Otomasi Sistem</option>
                        <option value="USER" {{ request('actor') == 'USER' ? 'selected' : '' }}>Petugas / Manual</option>
                    </select>
                </div>

                <!-- Submit & Reset Buttons -->
                <div class="md:col-span-2 flex items-end gap-2">
                    <button type="submit" class="flex-1 inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-2xs hover:bg-slate-800 transition-colors">
                        Saring
                    </button>
                    @if(request()->hasAny(['search', 'tahap', 'actor', 'tanggal_dari', 'tanggal_sampai']))
                        <a href="{{ route('timelines.index') }}" title="Reset Semua Filter" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Timeline Chronological Feed -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden p-6 sm:p-8">
        @php
            $groupedTimelines = $timelines->groupBy(function($item) {
                if ($item->tanggal_kejadian->isToday()) {
                    return 'Hari Ini • ' . $item->tanggal_kejadian->translatedFormat('d F Y');
                } elseif ($item->tanggal_kejadian->isYesterday()) {
                    return 'Kemarin • ' . $item->tanggal_kejadian->translatedFormat('d F Y');
                }
                return $item->tanggal_kejadian->translatedFormat('l, d F Y');
            });
        @endphp

        @if($timelines->isEmpty())
            <div class="text-center py-16">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 flex items-center justify-center text-gray-400">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-gray-900">Tidak ada riwayat aktivitas ditemukan</h3>
                <p class="text-sm text-gray-500 mt-1 max-w-sm mx-auto">
                    Kriteria pencarian atau filter yang Anda terapkan tidak menghasilkan catatan apapun.
                </p>
                <div class="mt-4">
                    <a href="{{ route('timelines.index') }}" class="inline-flex items-center gap-2 rounded-lg bg-red-50 px-4 py-2 text-sm font-semibold text-red-800 hover:bg-red-100 border border-red-200 transition">
                        Reset Filter Pencarian
                    </a>
                </div>
            </div>
        @else
            <div class="space-y-10">
                @foreach($groupedTimelines as $dateLabel => $items)
                    <div>
                        <!-- Date Header Divider -->
                        <div class="flex items-center gap-3 mb-6">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 border border-gray-200">
                                <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                {{ $dateLabel }}
                            </span>
                            <div class="flex-1 h-px bg-gray-200"></div>
                            <span class="text-xs text-gray-400 font-medium">{{ count($items) }} peristiwa</span>
                        </div>

                        <!-- Activities Stream in this date -->
                        <div class="relative border-l-2 border-gray-200 ml-3 sm:ml-5 space-y-6">
                            @foreach($items as $log)
                                @php
                                    $isSystem = ($log->actor_type === 'SYSTEM');
                                    $isOverdueAction = str_contains($log->status_kegiatan, 'Form-1B') || 
                                                       str_contains($log->status_kegiatan, 'Form-1C') || 
                                                       str_contains($log->status_kegiatan, 'P-20') || 
                                                       str_contains($log->status_kegiatan, 'FORM-7') || 
                                                       str_contains($log->status_kegiatan, 'FORM-3');
                                    
                                    // Tahap Badge Color Palette
                                    $tahapBg = match($log->tahap) {
                                        'Tahap II Selesai' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                        default => 'bg-slate-100 text-slate-700 border-slate-200',
                                    };
                                @endphp

                                <div class="relative pl-6 sm:pl-8 group">
                                    <!-- Node Indicator on the timeline rail -->
                                    <div class="absolute -left-[17px] top-1.5 flex items-center justify-center">
                                        @if($isSystem)
                                            <span class="relative flex h-8 w-8 items-center justify-center rounded-full bg-purple-600 text-white ring-4 ring-white shadow-sm">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                                                </svg>
                                            </span>
                                        @else
                                            <span class="relative flex h-8 w-8 items-center justify-center rounded-full bg-gray-900 text-white ring-4 ring-white shadow-sm group-hover:bg-red-700 transition">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" />
                                                </svg>
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Event Card Container -->
                                    <div class="bg-gray-50/70 hover:bg-white rounded-xl p-4 sm:p-5 border border-gray-200/80 hover:border-gray-300 hover:shadow-md transition duration-200">
                                        <!-- Top Row: Badges & Timestamps -->
                                        <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-gray-200/60">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <!-- Nomor Perkara Link -->
                                                @if($log->perkara)
                                                    <a href="{{ route('perkara.show', $log->perkara_id) }}" 
                                                       class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold bg-white text-red-800 border border-red-200 hover:bg-red-50 hover:border-red-300 transition shadow-2xs">
                                                        <span>SPDP: {{ $log->perkara->nomor_perkara }}</span>
                                                        <svg class="w-3 h-3 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                        </svg>
                                                    </a>
                                                @else
                                                    <span class="text-xs font-semibold text-gray-500">Perkara #{{ $log->perkara_id }}</span>
                                                @endif

                                                <!-- Tahap Badge -->
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider border {{ $tahapBg }}">
                                                    {{ $log->tahap }}
                                                </span>

                                                <!-- Actor Badge -->
                                                @if($isSystem)
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-purple-100/80 text-purple-800 border border-purple-200">
                                                        <svg class="w-3 h-3 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                                        </svg>
                                                        Otomasi SLA Sistem
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-medium bg-gray-200/70 text-gray-700">
                                                        <svg class="w-3 h-3 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                        </svg>
                                                        {{ $log->user->name ?? 'Petugas' }} 
                                                        @if(!empty($log->user->role))
                                                            <span class="text-gray-400">({{ strtoupper($log->user->role) }})</span>
                                                        @endif
                                                    </span>
                                                @endif
                                            </div>

                                            <!-- Timestamp -->
                                            <div class="text-right">
                                                <span class="text-xs text-gray-500 font-medium">
                                                    {{ $log->tanggal_kejadian->translatedFormat('d M Y') }} • 
                                                    <span class="font-semibold text-gray-700">{{ $log->created_at->timezone(config('app.timezone', 'Asia/Jakarta'))->format('H:i') }} WIB</span>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Event Title & Activity Description -->
                                        <div class="pt-3">
                                            <h4 class="text-base font-bold text-gray-900 flex items-center gap-2">
                                                @if($isOverdueAction)
                                                    <span class="inline-block w-2 h-2 rounded-full bg-rose-500"></span>
                                                @else
                                                    <span class="inline-block w-2 h-2 rounded-full bg-red-600"></span>
                                                @endif
                                                {{ $log->status_kegiatan }}
                                            </h4>

                                            <!-- Case Meta (Tersangka & Jaksa/Penyidik) -->
                                            @if($log->perkara)
                                                <div class="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-gray-600 bg-white/70 p-2.5 rounded-lg border border-gray-200/60">
                                                    <div>
                                                        <span class="text-gray-400 font-medium">Tersangka:</span>
                                                        <span class="font-semibold text-gray-800">
                                                            @if($log->perkara->tersangkas && $log->perkara->tersangkas->count() > 0)
                                                                {{ $log->perkara->tersangkas->pluck('nama')->implode(', ') }}
                                                            @else
                                                                -
                                                            @endif
                                                        </span>
                                                    </div>

                                                    <span class="text-gray-300">|</span>

                                                    <div>
                                                        <span class="text-gray-400 font-medium">Penyidik:</span>
                                                        <span class="font-medium text-gray-700">
                                                            @if($log->perkara->penyidiks && $log->perkara->penyidiks->count() > 0)
                                                                {{ $log->perkara->penyidiks->pluck('nama')->implode(', ') }}
                                                            @else
                                                                {{ $log->perkara->penyidik ?? '-' }}
                                                            @endif
                                                        </span>
                                                    </div>

                                                    <span class="text-gray-300">|</span>

                                                    <div>
                                                        <span class="text-gray-400 font-medium">Jaksa:</span>
                                                        <span class="font-medium text-gray-700">
                                                            @if($log->perkara->jaksas && $log->perkara->jaksas->count() > 0)
                                                                {{ $log->perkara->jaksas->pluck('name')->implode(', ') }}
                                                            @else
                                                                {{ $log->perkara->jaksa ?? '-' }}
                                                            @endif
                                                        </span>
                                                    </div>
                                                </div>
                                            @endif

                                            <!-- Catatan / Keterangan Event -->
                                            @if($log->catatan)
                                                <div class="mt-3 relative pl-3.5 py-1 border-l-2 border-red-300 bg-red-50/40 rounded-r-md">
                                                    <p class="text-xs sm:text-sm text-gray-700 italic">
                                                        "{{ $log->catatan }}"
                                                    </p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination Bar -->
            @if($timelines->hasPages())
                <div class="mt-8 pt-5 border-t border-gray-200">
                    {{ $timelines->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
