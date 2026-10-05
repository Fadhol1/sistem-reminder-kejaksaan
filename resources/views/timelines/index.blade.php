@extends('layouts.app')

@section('title', 'Global Timelines')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h3 class="text-xl font-semibold text-gray-900">Kronologis & Log Aktivitas (Timeline)</h3>
            <p class="text-sm text-gray-500 mt-1">Lacak setiap mutasi dan pergerakan status pada semua perkara secara real-time.</p>
        </div>
    </div>

    <!-- Search/Filter Filter -->
    <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
        <form action="{{ route('timelines.index') }}" method="GET" class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari aksi, catatan, nomor perkara, atau tersangka..." class="block w-full rounded-md border border-gray-300 py-2 px-3 text-sm focus-visible:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="inline-flex items-center justify-center rounded-md border border-transparent bg-gray-900 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-gray-800 focus:outline-none">
                    Cari Catatan
                </button>
                @if(request()->has('search'))
                    <a href="{{ route('timelines.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Activity Log List (Vertical Flow) -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden p-6 py-8">
        <div class="relative border-l border-gray-200 ml-4 lg:ml-6">
            @forelse($timelines as $log)
                <div class="mb-8 ml-6 group">
                    <span class="absolute flex items-center justify-center w-6 h-6 
                        @if($loop->first && request()->page <= 1) bg-indigo-500 ring-4 ring-indigo-50 text-white
                        @else bg-gray-100 ring-4 ring-white text-gray-500 group-hover:bg-indigo-100 group-hover:text-indigo-600 transition-colors @endif 
                        rounded-full -left-3 mt-1">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    
                    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4 pt-1">
                        <div class="relative z-10 w-full">
                            <h4 class="text-sm font-semibold text-gray-900 flex items-center flex-wrap gap-2">
                                <a href="{{ route('perkara.show', $log->perkara_id) }}" class="text-indigo-600 hover:underline hover:text-indigo-800 inline-block relative z-20">
                                    SPDP: {{ $log->perkara->nomor_perkara }}
                                </a>
                                <span class="bg-gray-100 text-gray-600 border border-gray-200 text-[10px] font-bold uppercase px-2 py-0.5 rounded">
                                    {{ $log->tahap }}
                                </span>
                            </h4>
                            <p class="text-sm font-medium text-gray-900 mt-1">
                                Status diubah menjadi: <span class="text-indigo-700">{{ $log->status_kegiatan }}</span>
                            </p>
                            @if($log->catatan)
                                <p class="text-sm text-gray-600 mt-2 bg-gray-50 p-3 rounded-md border border-gray-100 italic">
                                    "{{ $log->catatan }}"
                                </p>
                            @endif
                        </div>
                        
                        <div class="lg:text-right shrink-0">
                            <time class="block text-xs font-semibold text-gray-500 mb-1">
                                {{ $log->tanggal_kejadian->translatedFormat('d M Y, H:i') }}
                            </time>
                            <p class="text-xs text-gray-400">Oleh: <span class="font-medium text-gray-700">{{ $log->user->name ?? 'Sistem' }}</span></p>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-12 text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                      <path stroke-linecap="square" stroke-linejoin="miter" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Belum Ada Aktivitas</h3>
                    <p class="mt-1 text-sm text-gray-500">Belum ada satupun pergerakan perkara yang divalidasi dan tercatat sistem.</p>
                </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($timelines->hasPages())
            <div class="mt-8 pt-4 border-t border-gray-100">
                {{ $timelines->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
