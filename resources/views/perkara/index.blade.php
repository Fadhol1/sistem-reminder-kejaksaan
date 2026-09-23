@extends('layouts.app')

@section('title', 'Daftar Perkara (PIDUM)')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h3 class="text-xl font-semibold text-gray-900">Daftar Perkara (PIDUM)</h3>
            <p class="text-sm text-gray-500 mt-1">Kelola data dan pantau tahapan perkara PIDUM.</p>
        </div>
        <a href="{{ route('perkara.create') }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring bg-indigo-600 text-white shadow hover:bg-indigo-600/90 h-9 px-4 py-2">
            + Tambah Perkara
        </a>
    </div>

    @if(session('success'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
            <form action="{{ route('perkara.index') }}" method="GET" class="flex w-full md:w-1/2 space-x-2">
                <input type="text" name="search" placeholder="Cari No. Perkara / Tersangka..." value="{{ request('search') }}" class="flex h-9 w-full rounded-md border border-gray-300 bg-white px-3 py-1 text-sm shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600">
                <button type="submit" class="inline-flex items-center justify-center rounded-md text-sm font-medium bg-white border border-gray-300 shadow-sm hover:bg-gray-100 h-9 px-4 py-2">
                    Cari
                </button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left align-middle">
                <thead class="text-xs text-gray-700 uppercase bg-gray-50/50 border-b border-gray-200">
                    <tr>
                        <th class="px-6 py-3 font-medium">No. Perkara</th>
                        <th class="px-6 py-3 font-medium">Tersangka</th>
                        <th class="px-6 py-3 font-medium">Jaksa / Penyidik</th>
                        <th class="px-6 py-3 font-medium">Tahap</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                        <th class="px-6 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($perkaras as $perkara)
                    <tr class="hover:bg-gray-50/50">
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $perkara->nomor_perkara }}</td>
                        <td class="px-6 py-4">{{ $perkara->nama_tersangka }}</td>
                        <td class="px-6 py-4">
                            <div class="text-xs font-semibold text-gray-900">J: {{ $perkara->jaksa }}</div>
                            <div class="text-xs text-gray-500">P: {{ $perkara->penyidik }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">
                                {{ $perkara->tahap_saat_ini }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-xs font-medium text-gray-600">
                            {{ $perkara->status_saat_ini }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('perkara.show', $perkara->id) }}" class="inline-flex items-center justify-center rounded-md text-xs font-medium bg-white border border-gray-300 shadow-sm hover:bg-gray-100 h-8 px-3">
                                Detail
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500 text-sm">
                            Belum ada data perkara.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($perkaras->hasPages())
        <div class="p-4 border-t border-gray-200">
            {{ $perkaras->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
