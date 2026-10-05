@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center bg-indigo-50 p-6 rounded-xl border border-indigo-100">
        <div>
            <h3 class="text-xl font-bold text-indigo-900">Selamat datang, {{ auth()->user()->name }}</h3>
            <p class="text-sm text-indigo-700 mt-1">Ringkasan perkara dan reminder yang perlu perhatian Anda hari ini.</p>
        </div>
        <a href="{{ route('perkara.create') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium bg-indigo-600 text-white shadow hover:bg-indigo-700 h-9 px-4 py-2">
            Catat Perkara Baru
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('perkara.index') }}" class="block bg-white border border-gray-200 rounded-lg p-5 hover:bg-gray-50 transition-colors shadow-sm">
            <p class="text-sm font-medium text-gray-500">Total Perkara</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalPerkara }}</p>
        </a>

        <div class="bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Perkara Aktif</p>
            <p class="text-3xl font-bold text-indigo-600 mt-1">{{ $perkaraAktif }}</p>
        </div>

        <div class="bg-white border border-amber-200 bg-amber-50 rounded-lg p-5 shadow-sm">
            <p class="text-sm font-medium text-amber-700">Mendesak (H-3)</p>
            <p class="text-3xl font-bold text-amber-600 mt-1">{{ $reminderH3 }}</p>
        </div>

        <div class="bg-white border border-red-200 bg-red-50 rounded-lg p-5 shadow-sm">
            <p class="text-sm font-medium text-red-700">Overdue / Melewati SLA</p>
            <p class="text-3xl font-bold text-red-600 mt-1">{{ $reminderOverdue }}</p>
        </div>
    </div>

    @if(isset($urgentReminders) && count($urgentReminders) > 0)
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h4 class="font-semibold text-gray-900 text-sm">Daftar Deadline Mendesak & Overdue</h4>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left align-middle">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 font-medium">No. Perkara</th>
                        <th class="px-6 py-3 font-medium">Tersangka</th>
                        <th class="px-6 py-3 font-medium">Reminder</th>
                        <th class="px-6 py-3 font-medium">Deadline</th>
                        <th class="px-6 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($urgentReminders as $ur)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $ur->perkara->nomor_perkara }}</td>
                        <td class="px-6 py-4">
                            @php
                                $tersangkaList = $ur->perkara->tersangkas;
                                $firstNama = $tersangkaList->first()?->nama ?? '-';
                                $sisa = $tersangkaList->count() - 1;
                            @endphp
                            <div class="flex items-center gap-2">
                                <span>{{ $firstNama }}</span>
                                @if($sisa > 0)
                                    <button type="button" onclick="toggleTersangka('tersangka-{{ $ur->perkara_id }}-{{ $ur->id }}')" class="inline-flex items-center gap-0.5 text-indigo-600 hover:text-indigo-700 text-xs font-medium">
                                    +{{ $sisa }} lainnya
                                    <svg class="w-3 h-3 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div><span class="font-medium text-gray-900">{{ $ur->jenis_reminder }}</span></div>
                            <div class="mt-1">
                                <span class="inline-block rounded px-2 py-0.5 text-xs font-bold 
                                        @if($ur->status_urgensi === 'H-3') bg-amber-100 text-amber-700
                                        @else bg-red-100 text-red-700 @endif
                                    ">
                                    {{ $ur->status_urgensi }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 font-medium {{ in_array($ur->status_urgensi, ['Overdue', 'Jatuh Tempo']) ? 'text-red-600' : 'text-amber-600' }}">
                            {{ $ur->deadline->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('perkara.show', $ur->perkara_id) }}" class="inline-flex items-center justify-center rounded-md text-xs font-medium bg-white border border-gray-300 shadow-sm hover:bg-gray-100 h-8 px-3">
                                Lihat Perkara
                            </a>
                        </td>
                    </tr>
                    @if($tersangkaList && $tersangkaList->count() > 1)
                    <tr id="tersangka-{{ $ur->perkara_id }}-{{ $ur->id }}" class="hidden bg-gray-50/30">
                        <td></td>
                        <td colspan="4" class="px-6 py-3">
                            <div class="space-y-1">
                                @foreach($tersangkaList as $tersangka)
                                    <div class="flex items-center gap-3 text-xs text-gray-700 py-1 border-l-2 border-indigo-200 pl-3" style="max-width: 240px;">
                                        <span class="font-medium">{{ $loop->iteration }}.</span>
                                        <span>{{ $tersangka->nama }}</span>
                                    </div>
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