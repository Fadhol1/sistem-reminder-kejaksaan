@extends('layouts.app')

@section('title', 'Pemantauan Batas Waktu SLA (Reminders)')

@section('content')
<div class="space-y-6 w-full mx-auto">
    <!-- Header Page -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2 border-b border-gray-200">
        <div>
            <div class="flex items-center gap-2">
                <span class="p-2 rounded-lg bg-red-50 text-red-700 border border-red-200">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                    </svg>
                </span>
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 tracking-tight">Pemantauan Batas Waktu SLA (Reminders)</h2>
                    <p class="text-sm text-gray-500">Pantau jatuh tempo dan kepatuhan tenggat waktu seluruh tahapan perkara aktif.</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-red-50 text-red-800 border border-red-200">
                <span class="w-2 h-2 rounded-full bg-red-600 animate-pulse"></span>
                Monitoring Otomatis SLA
            </span>
        </div>
    </div>

    <!-- 4 Stats / KPI Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
        <!-- Card 1: Total Aktif -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-2xs flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Reminder Aktif</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($stats['total_aktif'] ?? 0) }}</h3>
                <p class="text-[11px] text-gray-400 mt-0.5">Sedang berjalan di sistem</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-gray-50 text-gray-700 border border-gray-200 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>

        <!-- Card 2: Overdue -->
        <div class="bg-white p-4 rounded-xl border {{ ($stats['overdue'] ?? 0) > 0 ? 'border-rose-300 bg-rose-50/20' : 'border-gray-200' }} shadow-2xs flex items-center justify-between">
            <div>
                <div class="flex items-center gap-1.5">
                    <p class="text-xs font-medium text-rose-700 uppercase tracking-wider">Overdue (Lewat SLA)</p>
                    @if(($stats['overdue'] ?? 0) > 0)
                        <span class="inline-flex items-center px-1.5 py-0.2 rounded-full text-[10px] font-bold bg-rose-100 text-rose-700">Segera Tindak</span>
                    @endif
                </div>
                <h3 class="text-2xl font-bold text-rose-600 mt-0.5">{{ number_format($stats['overdue'] ?? 0) }}</h3>
                <p class="text-[11px] text-gray-400 mt-0.5">Memerlukan tindak lanjut</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
            </div>
        </div>

        <!-- Card 3: Jatuh Tempo Hari Ini -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-2xs flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-rose-700 uppercase tracking-wider">Jatuh Tempo Hari Ini</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($stats['jatuh_tempo'] ?? 0) }}</h3>
                <p class="text-[11px] text-gray-400 mt-0.5">Batas waktu hari ini (H-0)</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-rose-50 text-rose-600 border border-rose-200 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
            </div>
        </div>

        <!-- Card 4: Siaga H-3 -->
        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-2xs flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-amber-700 uppercase tracking-wider">Siaga (H-3 s/d H-1)</p>
                <h3 class="text-2xl font-bold text-gray-900 mt-0.5">{{ number_format($stats['h_3'] ?? 0) }}</h3>
                <p class="text-[11px] text-gray-400 mt-0.5">Mendekati batas waktu</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 border border-amber-200 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- Search/Filter Controls -->
    <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-2xs">
        <form action="{{ route('reminders.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-4 w-4 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}" 
                    placeholder="Cari berdasarkan Nomor SPDP, tersangka, atau jenis reminder..." 
                    class="block w-full rounded-lg border border-gray-300 py-2 pl-9 pr-3 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/20">
            </div>

            <div class="sm:w-64">
                <select name="urgensi" class="block w-full rounded-lg border border-gray-300 py-2 px-3 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/20">
                    <option value="">Semua Tingkat Urgensi</option>
                    <option value="semua_mendesak" {{ request('urgensi') == 'semua_mendesak' ? 'selected' : '' }}>Hanya Menampilkan Mendesak</option>
                    <option value="Overdue" {{ request('urgensi') == 'Overdue' ? 'selected' : '' }}>Overdue / Lewat Batas</option>
                    <option value="Jatuh Tempo" {{ request('urgensi') == 'Jatuh Tempo' ? 'selected' : '' }}>Hari Ini (Jatuh Tempo)</option>
                    <option value="H-3" {{ request('urgensi') == 'H-3' ? 'selected' : '' }}>H-3 (Siaga)</option>
                    <option value="Aman" {{ request('urgensi') == 'Aman' ? 'selected' : '' }}>Aman (> 3 Hari)</option>
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-2xs hover:bg-slate-800 transition-colors">
                    Cari & Saring
                </button>
                @if(request()->hasAny(['search', 'urgensi']))
                    <a href="{{ route('reminders.index') }}" title="Reset Filter" class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table List -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left align-middle">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50/80 border-b border-gray-200 font-semibold tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">No. Perkara / SPDP</th>
                        <th class="px-6 py-3.5">Tahap & Jenis Reminder</th>
                        <th class="px-6 py-3.5">Status Urgensi</th>
                        <th class="px-6 py-3.5">Batas SLA & Sisa Waktu</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($reminders as $ur)
                        @php
                            $sisaHari = $ur->sisa_hari;
                            $tahapBg = match($ur->tahap) {
                                'Tahap II Selesai' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                        @endphp
                        <tr class="hover:bg-gray-50/60 transition-colors">
                            <!-- Kolom Perkara -->
                            <td class="px-6 py-4">
                                <a href="{{ route('perkara.show', $ur->perkara_id) }}" class="font-bold text-gray-900 hover:text-red-700 hover:underline inline-flex items-center gap-1.5">
                                    {{ $ur->perkara->nomor_perkara ?? 'Perkara #' . $ur->perkara_id }}
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                </a>
                                <div class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                                    <span class="text-gray-400">Tersangka:</span>
                                    <span class="font-medium text-gray-700">
                                        @php
                                            $tersangkaList = $ur->perkara?->tersangkas ?? collect();
                                            $firstNama = $tersangkaList->first()?->nama ?? '-';
                                            $sisa = $tersangkaList->count() - 1;
                                        @endphp
                                        {{ $firstNama }} @if($sisa > 0) <span class="font-semibold text-gray-500">(+{{ $sisa }} lainnya)</span> @endif
                                    </span>
                                </div>
                            </td>

                            <!-- Kolom Reminder & Tahap -->
                            <td class="px-6 py-4">
                                <div class="font-semibold text-gray-900">{{ $ur->jenis_reminder }}</div>
                                <div class="mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium border {{ $tahapBg }}">
                                        {{ $ur->tahap }}
                                    </span>
                                </div>
                            </td>

                            <!-- Kolom Urgensi -->
                            <td class="px-6 py-4">
                                @if($ur->status_urgensi === 'Overdue')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-600 animate-pulse"></span>
                                        Overdue (Lewat SLA)
                                    </span>
                                @elseif($ur->status_urgensi === 'Jatuh Tempo')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-900 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                        Hari Ini (Jatuh Tempo)
                                    </span>
                                @elseif($ur->status_urgensi === 'H-3')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                        H-3 (Siaga)
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        Aman
                                    </span>
                                @endif
                            </td>

                            <!-- Kolom Deadline & Sisa Hari -->
                            <td class="px-6 py-4">
                                <div class="font-bold {{ in_array($ur->status_urgensi, ['Overdue', 'Jatuh Tempo']) ? 'text-rose-600' : ($ur->status_urgensi === 'H-3' ? 'text-amber-700' : 'text-gray-900') }}">
                                    {{ $ur->deadline->translatedFormat('d M Y') }}
                                </div>
                                <div class="text-xs mt-0.5">
                                    @if($sisaHari < 0)
                                        <span class="font-semibold text-rose-600">Lewat {{ abs($sisaHari) }} hari</span>
                                    @elseif($sisaHari === 0)
                                        <span class="font-semibold text-rose-600">Berakhir Hari Ini</span>
                                    @else
                                        <span class="text-gray-500">Tersisa <strong class="text-gray-700">{{ $sisaHari }} hari</strong></span>
                                    @endif
                                </div>
                            </td>

                            <!-- Kolom Aksi -->
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('perkara.show', $ur->perkara_id) }}" 
                                   class="inline-flex items-center gap-1.5 rounded-lg text-xs font-semibold bg-slate-900 text-white hover:bg-slate-800 shadow-2xs py-1.5 px-3 transition-colors">
                                    <span>Buka Perkara</span>
                                    <svg class="w-3.5 h-3.5 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-14 text-center">
                                <div class="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 flex items-center justify-center text-gray-400">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                    </svg>
                                </div>
                                <h4 class="text-sm font-semibold text-gray-900">Tidak ada reminder yang sesuai</h4>
                                <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">
                                    Semua tenggat waktu sedang dalam kondisi baik atau filter yang Anda terapkan tidak memiliki data.
                                </p>
                                @if(request()->hasAny(['search', 'urgensi']))
                                    <div class="mt-3">
                                        <a href="{{ route('reminders.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-red-700 hover:text-red-900">
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
        @if($reminders->hasPages())
            <div class="bg-white px-5 py-3.5 border-t border-gray-200">
                {{ $reminders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
