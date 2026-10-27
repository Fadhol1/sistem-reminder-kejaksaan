@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#700d1a] via-[#881337] to-[#450a0a] p-6 text-white shadow-md">
        <!-- Decorative background emblem/glow -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-white/5 rounded-full blur-2xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-white/10 text-amber-300 text-xs font-semibold mb-2 backdrop-blur-xs border border-white/15">
                    <svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                    </svg>
                    <span>Sistem Pengawasan Tenggat Waktu Pidum</span>
                </div>
                <h3 class="text-xl sm:text-2xl font-bold tracking-tight text-white">Selamat datang, {{ auth()->user()->name }}</h3>
                <p class="text-sm text-red-100/90 mt-1 max-w-xl">Ringkasan perkara dan reminder batas waktu (SLA) yang memerlukan koordinasi dan tindakan tindak lanjut Anda hari ini.</p>
            </div>
            @if(auth()->user()->isPidum())
            <a href="{{ route('perkara.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-[#450a0a] font-bold text-sm px-4 py-2.5 shadow-md hover:shadow-lg transition-all shrink-0">
                <svg class="w-4 h-4 text-[#450a0a]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                <span>Catat Perkara Baru</span>
            </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('perkara.index') }}" class="block bg-white border border-gray-200 rounded-xl p-5 hover:border-gray-300 hover:shadow-md transition shadow-sm">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Perkara</p>
            <p class="text-3xl font-extrabold text-gray-900 mt-1.5">{{ number_format($totalPerkara) }}</p>
            <p class="text-xs text-gray-400 mt-1">Semua data terdaftar</p>
        </a>

        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
            <p class="text-xs font-semibold text-red-700 uppercase tracking-wider">Perkara Aktif</p>
            <p class="text-3xl font-extrabold text-red-700 mt-1.5">{{ number_format($perkaraAktif) }}</p>
            <p class="text-xs text-gray-400 mt-1">Dalam proses penanganan</p>
        </div>

        <div class="bg-white border border-amber-200 bg-amber-50/50 rounded-xl p-5 shadow-sm">
            <p class="text-xs font-semibold text-amber-800 uppercase tracking-wider">Mendesak (H-3)</p>
            <p class="text-3xl font-extrabold text-amber-600 mt-1.5">{{ number_format($reminderH3) }}</p>
            <p class="text-xs text-amber-700/80 mt-1">Mendekati batas waktu</p>
        </div>

        <div class="bg-white border border-red-200 bg-red-50/50 rounded-xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <p class="text-xs font-semibold text-red-700 uppercase tracking-wider">Overdue / Lewat SLA</p>
                @if($reminderOverdue > 0)
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700 animate-pulse">Aksi Segera</span>
                @endif
            </div>
            <p class="text-3xl font-extrabold text-red-600 mt-1.5">{{ number_format($reminderOverdue) }}</p>
            <p class="text-xs text-red-700/80 mt-1">Perlu tindakan langsung</p>
        </div>
    </div>

    @if(isset($urgentReminders) && count($urgentReminders) > 0)
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-red-600 animate-ping"></span>
                <h4 class="font-bold text-gray-900 text-sm">Daftar Deadline Mendesak & Overdue</h4>
            </div>
            <a href="{{ route('reminders.index') }}" class="text-xs font-semibold text-red-700 hover:text-red-900 hover:underline">
                Lihat Semua Reminder →
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left align-middle">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50/80 border-b border-gray-200 font-semibold tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">No. Perkara</th>
                        <th class="px-6 py-3.5">Tersangka</th>
                        <th class="px-6 py-3.5">Reminder</th>
                        <th class="px-6 py-3.5">Deadline</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($urgentReminders as $ur)
                    <tr class="hover:bg-gray-50/60 transition-colors">
                        <td class="px-6 py-4 font-bold text-gray-900">
                            <a href="{{ route('perkara.show', $ur->perkara_id) }}" class="hover:text-red-700 hover:underline">
                                {{ $ur->perkara->nomor_perkara }}
                            </a>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $tersangkaList = $ur->perkara->tersangkas;
                                $firstNama = $tersangkaList->first()?->nama ?? '-';
                                $sisa = $tersangkaList->count() - 1;
                            @endphp
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-900">{{ $firstNama }}</span>
                                @if($sisa > 0)
                                    <button type="button" onclick="toggleTersangka('tersangka-{{ $ur->perkara_id }}-{{ $ur->id }}')" class="inline-flex items-center gap-0.5 text-red-700 hover:text-red-900 text-xs font-semibold bg-red-50 border border-red-200 px-1.5 py-0.5 rounded">
                                        +{{ $sisa }} lainnya
                                        <svg class="w-3 h-3 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div><span class="font-semibold text-gray-900">{{ $ur->jenis_reminder }}</span></div>
                            <div class="mt-1">
                                <span class="inline-flex items-center gap-1 rounded px-2 py-0.5 text-xs font-bold 
                                        @if($ur->status_urgensi === 'H-3') bg-amber-100 text-amber-800 border border-amber-200
                                        @else bg-red-100 text-red-800 border border-red-200 @endif
                                    ">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $ur->status_urgensi === 'H-3' ? 'bg-amber-600' : 'bg-red-600 animate-pulse' }}"></span>
                                    {{ $ur->status_urgensi }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 font-semibold {{ in_array($ur->status_urgensi, ['Overdue', 'Jatuh Tempo']) ? 'text-red-600' : 'text-amber-700' }}">
                            {{ $ur->deadline->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('perkara.show', $ur->perkara_id) }}" class="inline-flex items-center gap-1 rounded-lg text-xs font-semibold bg-white border border-gray-300 shadow-2xs hover:bg-gray-50 hover:border-gray-400 py-1.5 px-3 transition text-gray-700">
                                <span>Lihat Perkara</span>
                                <svg class="w-3 h-3 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @if($tersangkaList && $tersangkaList->count() > 1)
                    <tr id="tersangka-{{ $ur->perkara_id }}-{{ $ur->id }}" class="hidden bg-gray-50/70">
                        <td></td>
                        <td colspan="4" class="px-6 py-3">
                            <div class="text-xs text-gray-600 font-medium mb-1.5">Daftar Lengkap Tersangka:</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach($tersangkaList as $tersangka)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs bg-white text-gray-800 border border-gray-200 shadow-2xs">
                                        <span class="font-bold text-red-700">{{ $loop->iteration }}.</span>
                                        <span>{{ $tersangka->nama }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<script>
    function toggleTersangka(id) {
        const el = document.getElementById(id);
        if (el) el.classList.toggle('hidden');
    }
</script>
@endsection