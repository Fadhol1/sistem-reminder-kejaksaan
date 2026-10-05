@extends('layouts.app')

@section('title', 'Global Reminders')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h3 class="text-xl font-semibold text-gray-900">Pemantauan Batas Waktu (Reminders)</h3>
            <p class="text-sm text-gray-500 mt-1">Daftar semua batas SLA Perkara yang sedang berjalan.</p>
        </div>
    </div>

    <!-- Search/Filter Filter -->
    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
        <form action="{{ route('reminders.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari berdasarkan Nomor SPDP atau Tersangka..." class="block w-full rounded-md border border-gray-300 py-2 px-3 text-sm focus-visible:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
            <div class="sm:w-56">
                <select name="urgensi" class="block w-full rounded-md border border-gray-300 py-2 px-3 text-sm focus-visible:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    <option value="">Semua Status Urgensi</option>
                    <option value="semua_mendesak" {{ request('urgensi') == 'semua_mendesak' ? 'selected' : '' }}>Hanya Menampilkan Mendesak</option>
                    <option value="Aman" {{ request('urgensi') == 'Aman' ? 'selected' : '' }}>Aman</option>
                    <option value="H-3" {{ request('urgensi') == 'H-3' ? 'selected' : '' }}>H-3 / Siaga</option>
                    <option value="Jatuh Tempo" {{ request('urgensi') == 'Jatuh Tempo' ? 'selected' : '' }}>Hari Ini (Jatuh Tempo)</option>
                    <option value="Overdue" {{ request('urgensi') == 'Overdue' ? 'selected' : '' }}>Overdue / Lewat SLA</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="inline-flex items-center justify-center rounded-md border border-transparent bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none">
                    Cari & Saring
                </button>
                @if(request()->has('search') || request()->has('urgensi'))
                    <a href="{{ route('reminders.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left align-middle">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 font-medium">No. Perkara / SPDP</th>
                        <th class="px-6 py-3 font-medium">Detail Reminder</th>
                        <th class="px-6 py-3 font-medium">Deadline SLA</th>
                        <th class="px-6 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($reminders as $ur)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <!-- Kolom Perkara -->
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $ur->perkara->nomor_perkara }}</div>
                                <div class="text-xs text-gray-500 mt-1">
                                    Tersangka: 
                                    @php
                                        $tersangkaList = $ur->perkara->tersangkas;
                                        $firstNama = $tersangkaList->first()?->nama ?? '-';
                                        $sisa = $tersangkaList->count() - 1;
                                    @endphp
                                    {{ $firstNama }} @if($sisa > 0) <span class="font-semibold text-gray-700"> (+{{ $sisa }} Lainnya)</span> @endif
                                </div>
                            </td>
                            <!-- Kolom Reminder -->
                            <td class="px-6 py-4">
                                <div><span class="font-medium text-gray-900">{{ $ur->jenis_reminder }}</span> di Tahap <span class="font-semibold text-gray-700">{{ $ur->tahap }}</span></div>
                                <div class="mt-1.5">
                                    <span class="inline-block rounded px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider
                                            @if(in_array($ur->status_urgensi, ['Overdue', 'Jatuh Tempo'])) bg-red-100 text-red-700 border border-red-200
                                            @elseif($ur->status_urgensi === 'H-3') bg-amber-100 text-amber-700 border border-amber-200
                                            @else bg-green-100 text-green-700 border border-green-200 @endif
                                        ">
                                        {{ $ur->status_urgensi }}
                                    </span>
                                </div>
                            </td>
                            <!-- Kolom Deadline -->
                            <td class="px-6 py-4 font-semibold {{ in_array($ur->status_urgensi, ['Overdue', 'Jatuh Tempo']) ? 'text-red-600' : ($ur->status_urgensi == 'H-3' ? 'text-amber-600' : 'text-gray-900') }}">
                                {{ $ur->deadline->format('d M Y') }}
                                <div class="text-[10px] uppercase font-normal text-gray-500 mt-1">Mulai: {{ $ur->tanggal_mulai->format('d/m/y') }}</div>
                            </td>
                            <!-- Kolom Aksi -->
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('perkara.show', $ur->perkara_id) }}" class="inline-flex items-center justify-center rounded-md text-xs font-medium bg-white border border-gray-300 shadow-sm hover:bg-gray-100 h-8 px-3 transition-colors">
                                    Buka Perkara
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-10 text-center text-gray-500 text-sm">
                                Tidak ada data reminder yang berjalan/ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($reminders->hasPages())
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $reminders->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
