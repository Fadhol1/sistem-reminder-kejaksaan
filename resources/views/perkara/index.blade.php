@extends('layouts.app')

@section('title', 'Daftar Perkara (PIDUM)')

@section('content')
<div class="space-y-6 w-full mx-auto">
    <!-- Header Page -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2 border-b border-gray-200">
        <div>
            <div class="flex items-center gap-2">
                <span class="p-2 rounded-lg bg-red-50 text-red-700 border border-red-200">
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
                <a href="{{ route('perkara.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-[#881337] via-[#991b1b] to-[#7f1d1d] hover:from-[#700d1a] hover:via-[#881337] hover:to-[#5c0b15] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition">
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
                <p class="text-xs text-gray-400 mt-0.5">Tahap II, FORM-3, P-20 & FORM-7</p>
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
                            class="block w-full rounded-lg border border-gray-300 py-2 pl-9 pr-3 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/20">
                    </div>
                </div>

                <!-- Tahapan Dropdown -->
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

                <!-- Status Filter Dropdown -->
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Status Penanganan</label>
                    <select name="status_filter" class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/20">
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
                                <a href="{{ route('perkara.show', $perkara->id) }}" class="font-bold text-gray-900 hover:text-red-700 hover:underline inline-flex items-center gap-1.5">
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
                                        <button type="button" onclick="openTablePopover('popover-tersangka-{{ $perkara->id }}', this, event)" 
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[11px] font-semibold bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 transition-colors shadow-2xs group"
                                            title="Lihat seluruh tersangka">
                                            <span>+{{ $sisa }} lainnya</span>
                                            <svg class="w-3 h-3 text-red-500 group-hover:text-red-700 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>

                            <!-- Kolom Jaksa & Penyidik -->
                            <td class="px-6 py-4">
                                @php
                                    $jaksaList = $perkara->jaksas;
                                    $firstJaksa = ($jaksaList && $jaksaList->count() > 0) ? $jaksaList->first()->name : ($perkara->jaksa ?? '-');
                                    $sisaJaksa = $jaksaList ? max(0, $jaksaList->count() - 1) : 0;

                                    $penyidikList = $perkara->penyidiks;
                                    $firstPenyidik = ($penyidikList && $penyidikList->count() > 0) ? $penyidikList->first()->nama : ($perkara->penyidik ?? '-');
                                    $sisaPenyidik = $penyidikList ? max(0, $penyidikList->count() - 1) : 0;
                                @endphp

                                <div class="text-xs font-semibold text-gray-900 flex items-center gap-1.5 flex-wrap">
                                    <span class="text-gray-400 font-normal">Jaksa:</span>
                                    <span>{{ $firstJaksa }}</span>
                                    @if($sisaJaksa > 0)
                                        <button type="button" onclick="openTablePopover('popover-jaksa-{{ $perkara->id }}', this, event)" 
                                            class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200 transition-colors shadow-2xs group"
                                            title="Lihat seluruh jaksa penuntut umum">
                                            <span>+{{ $sisaJaksa }} lainnya</span>
                                            <svg class="w-2.5 h-2.5 text-emerald-500 group-hover:text-emerald-700 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>

                                <div class="text-xs text-gray-500 mt-1 flex items-center gap-1.5 flex-wrap">
                                    <span class="text-gray-400">Penyidik:</span>
                                    <span>{{ $firstPenyidik }}</span>
                                    @if($sisaPenyidik > 0)
                                        <button type="button" onclick="openTablePopover('popover-penyidik-{{ $perkara->id }}', this, event)" 
                                            class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 transition-colors shadow-2xs group"
                                            title="Lihat seluruh penyidik">
                                            <span>+{{ $sisaPenyidik }} lainnya</span>
                                            <svg class="w-2.5 h-2.5 text-blue-500 group-hover:text-blue-700 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                            </svg>
                                        </button>
                                    @endif
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
                                            ✓ SPDP Dikembalikan (FORM-3)
                                        </span>
                                    @elseif(str_contains($perkara->status_saat_ini, 'P-20'))
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-300">
                                            ✓ SPDP Dikembalikan (P-20)
                                        </span>
                                    @elseif(str_contains($perkara->status_saat_ini, 'FORM-7'))
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-300">
                                            ✓ Berkas & SPDP Dikembalikan (FORM-7)
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- Kolom Aksi -->
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <a href="{{ route('perkara.show', $perkara->id) }}" 
                                       class="inline-flex items-center gap-1 rounded-lg text-xs font-semibold bg-white border border-gray-300 shadow-2xs hover:bg-gray-50 hover:border-gray-400 py-1.5 px-2.5 transition text-gray-700"
                                       title="Lihat Detail & Riwayat Perkara">
                                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                        <span>Detail</span>
                                    </a>
                                    @if(auth()->user()->isPidum())
                                        <a href="{{ route('perkara.edit', $perkara->id) }}" 
                                            class="inline-flex items-center gap-1 rounded-lg text-xs font-semibold bg-amber-50 text-amber-900 border border-amber-300 shadow-2xs hover:bg-amber-100 hover:border-amber-400 py-1.5 px-2.5 transition"
                                            title="Ubah Data Perkara">
                                            <svg class="w-3.5 h-3.5 text-amber-700" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                                            </svg>
                                            <span>Edit</span>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
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
                                        <a href="{{ route('perkara.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-700 hover:text-red-900">
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

    <!-- Floating Popover Cards (Rendered outside overflow containers) -->
    <div id="table-popovers-container">
        @foreach($perkaras as $perkara)
            <!-- Popover Tersangka -->
            @if($perkara->tersangkas && $perkara->tersangkas->count() > 1)
                <div id="popover-tersangka-{{ $perkara->id }}" class="table-popover hidden fixed z-50 w-72 sm:w-80 bg-white rounded-xl shadow-2xl border border-gray-200/90 overflow-hidden">
                    <div class="px-3.5 py-2.5 bg-gradient-to-r from-red-50 to-rose-50/60 border-b border-red-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-lg bg-red-600 text-white flex items-center justify-center shadow-2xs">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-gray-900 leading-tight">Daftar Tersangka</h4>
                                <p class="text-[10px] text-gray-500 font-medium">Perkara: {{ $perkara->nomor_perkara }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700">
                            {{ $perkara->tersangkas->count() }} Orang
                        </span>
                    </div>
                    <div class="max-h-60 overflow-y-auto divide-y divide-gray-100 p-1">
                        @foreach($perkara->tersangkas as $tItem)
                            <div class="p-2 flex items-center gap-2.5 rounded-lg hover:bg-gray-50 transition-colors">
                                <span class="w-5 h-5 rounded-md bg-red-50 text-red-700 font-bold text-[11px] flex items-center justify-center shrink-0 border border-red-100">
                                    {{ $loop->iteration }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs font-semibold text-gray-900 truncate">
                                        {{ $tItem->nama }}
                                    </div>
                                    <div class="text-[10px] text-gray-400">Tersangka Perkara</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="px-3 py-2 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between text-[10px] text-gray-400">
                        <span>Tekan ESC untuk tutup</span>
                        <button type="button" onclick="closeAllPopovers()" class="font-semibold text-red-600 hover:text-red-800 hover:underline">
                            Tutup
                        </button>
                    </div>
                </div>
            @endif

            <!-- Popover Penyidik -->
            @php
                $popoverPenyidikList = $perkara->penyidiks;
            @endphp
            @if($popoverPenyidikList && $popoverPenyidikList->count() > 1)
                <div id="popover-penyidik-{{ $perkara->id }}" class="table-popover hidden fixed z-50 w-72 sm:w-80 bg-white rounded-xl shadow-2xl border border-gray-200/90 overflow-hidden">
                    <div class="px-3.5 py-2.5 bg-gradient-to-r from-blue-50 to-indigo-50/60 border-b border-blue-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-lg bg-blue-600 text-white flex items-center justify-center shadow-2xs">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-gray-900 leading-tight">Daftar Tim Penyidik</h4>
                                <p class="text-[10px] text-gray-500 font-medium">Perkara: {{ $perkara->nomor_perkara }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700">
                            {{ $popoverPenyidikList->count() }} Orang
                        </span>
                    </div>
                    <div class="max-h-60 overflow-y-auto divide-y divide-gray-100 p-1">
                        @foreach($popoverPenyidikList as $penyidikItem)
                            <div class="p-2 flex items-center gap-2.5 rounded-lg hover:bg-gray-50 transition-colors">
                                <span class="w-5 h-5 rounded-md bg-blue-50 text-blue-700 font-bold text-[11px] flex items-center justify-center shrink-0 border border-blue-100">
                                    {{ $loop->iteration }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs font-semibold text-gray-900 truncate">
                                        {{ $penyidikItem->nama }}
                                    </div>
                                    <div class="text-[10px] text-gray-400">Penyidik Kepolisian</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="px-3 py-2 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between text-[10px] text-gray-400">
                        <span>Tekan ESC untuk tutup</span>
                        <button type="button" onclick="closeAllPopovers()" class="font-semibold text-blue-600 hover:text-blue-800 hover:underline">
                            Tutup
                        </button>
                    </div>
                </div>
            @endif

            <!-- Popover Jaksa -->
            @php
                $popoverJaksaList = $perkara->jaksas;
            @endphp
            @if($popoverJaksaList && $popoverJaksaList->count() > 1)
                <div id="popover-jaksa-{{ $perkara->id }}" class="table-popover hidden fixed z-50 w-72 sm:w-80 bg-white rounded-xl shadow-2xl border border-gray-200/90 overflow-hidden">
                    <div class="px-3.5 py-2.5 bg-gradient-to-r from-emerald-50 to-teal-50/60 border-b border-emerald-100 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-lg bg-emerald-600 text-white flex items-center justify-center shadow-2xs">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 01-2.031.352 5.988 5.988 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 01-2.031.352 5.989 5.989 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-gray-900 leading-tight">Daftar Jaksa Penuntut Umum</h4>
                                <p class="text-[10px] text-gray-500 font-medium">Perkara: {{ $perkara->nomor_perkara }}</p>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700">
                            {{ $popoverJaksaList->count() }} Orang
                        </span>
                    </div>
                    <div class="max-h-60 overflow-y-auto divide-y divide-gray-100 p-1">
                        @foreach($popoverJaksaList as $j)
                            <div class="p-2 flex items-center gap-2.5 rounded-lg hover:bg-gray-50 transition-colors">
                                <span class="w-5 h-5 rounded-md bg-emerald-50 text-emerald-700 font-bold text-[11px] flex items-center justify-center shrink-0 border border-emerald-100">
                                    {{ $loop->iteration }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs font-semibold text-gray-900 truncate">
                                        {{ $j->name }}
                                    </div>
                                    <div class="text-[10px] text-gray-400">Jaksa Penuntut Umum</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="px-3 py-2 bg-gray-50/80 border-t border-gray-100 flex items-center justify-between text-[10px] text-gray-400">
                        <span>Tekan ESC untuk tutup</span>
                        <button type="button" onclick="closeAllPopovers()" class="font-semibold text-emerald-600 hover:text-emerald-800 hover:underline">
                            Tutup
                        </button>
                    </div>
                </div>
            @endif
        @endforeach
    </div>
</div>

<script>
    let currentActivePopover = null;
    let currentActiveButton = null;

    function closeAllPopovers() {
        document.querySelectorAll('.table-popover').forEach(p => {
            p.classList.add('hidden');
        });
        if (currentActiveButton) {
            const svg = currentActiveButton.querySelector('svg');
            if (svg) svg.classList.remove('rotate-180');
        }
        currentActivePopover = null;
        currentActiveButton = null;
    }

    function openTablePopover(id, btn, event) {
        if (event) {
            event.stopPropagation();
        }

        const popover = document.getElementById(id);
        if (!popover) return;

        // Jika mengklik tombol yang sedang terbuka, toggle tutup
        if (currentActivePopover === popover) {
            closeAllPopovers();
            return;
        }

        // Tutup popover lain yang aktif
        closeAllPopovers();

        currentActivePopover = popover;
        currentActiveButton = btn;

        const svg = btn.querySelector('svg');
        if (svg) svg.classList.add('rotate-180');

        // Buka popover terlebih dahulu untuk mengukur dimensi
        popover.classList.remove('hidden');

        const rect = btn.getBoundingClientRect();
        const popoverRect = popover.getBoundingClientRect();

        // Posisi vertikal: jika dekat tepi bawah layar, balik ke atas tombol
        const spaceBelow = window.innerHeight - rect.bottom;
        const spaceAbove = rect.top;
        let top;

        if (spaceBelow < popoverRect.height + 12 && spaceAbove > spaceBelow) {
            top = rect.top - popoverRect.height - 6;
        } else {
            top = rect.bottom + 6;
        }

        // Posisi horizontal: sejajar dengan tombol, tapi jangan tembus tepi layar
        let left = rect.left;
        const maxLeft = window.innerWidth - popoverRect.width - 16;
        if (left > maxLeft) {
            left = maxLeft;
        }
        if (left < 16) {
            left = 16;
        }

        popover.style.top = `${Math.round(top)}px`;
        popover.style.left = `${Math.round(left)}px`;
    }

    // Klik di luar popover untuk menutup
    document.addEventListener('click', function(e) {
        if (currentActivePopover && !currentActivePopover.contains(e.target)) {
            closeAllPopovers();
        }
    });

    // Menutup saat scroll agar tidak melayang di tempat yang salah
    window.addEventListener('scroll', function() {
        if (currentActivePopover) {
            closeAllPopovers();
        }
    }, { passive: true, capture: true });

    // Menutup saat resize jendela
    window.addEventListener('resize', function() {
        if (currentActivePopover) {
            closeAllPopovers();
        }
    }, { passive: true });

    // Menutup dengan tombol Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && currentActivePopover) {
            closeAllPopovers();
        }
    });
</script>
@endsection