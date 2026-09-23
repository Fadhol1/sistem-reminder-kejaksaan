@extends('layouts.app')

@section('title', 'Tambah Perkara (PIDUM)')

@section('content')
<div class="space-y-6 max-w-3xl mx-auto">
    <div class="flex items-center space-x-3">
        <a href="{{ route('perkara.index') }}" class="text-gray-500 hover:text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        </a>
        <h3 class="text-xl font-semibold text-gray-900">Tambah Perkara Baru (PIDUM)</h3>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden p-6">
        <form action="{{ route('perkara.store') }}" method="POST" class="space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Bidang dicatat otomatis di backend sbg PIDUM -->
                
                <div class="col-span-1 md:col-span-2">
                    <label for="nomor_perkara" class="block text-sm font-medium text-gray-700 mb-1">Nomor Perkara / SPDP</label>
                    <input type="text" name="nomor_perkara" id="nomor_perkara" value="{{ old('nomor_perkara') }}" required class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600 @error('nomor_perkara') border-red-500 @enderror">
                    @error('nomor_perkara')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="tanggal_spdp" class="block text-sm font-medium text-gray-700 mb-1">Tanggal SPDP</label>
                    <input type="date" name="tanggal_spdp" id="tanggal_spdp" value="{{ old('tanggal_spdp', date('Y-m-d')) }}" required class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600">
                    @error('tanggal_spdp')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="nama_tersangka" class="block text-sm font-medium text-gray-700 mb-1">Nama Tersangka</label>
                    <input type="text" name="nama_tersangka" id="nama_tersangka" value="{{ old('nama_tersangka') }}" required class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600">
                    @error('nama_tersangka')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="penyidik" class="block text-sm font-medium text-gray-700 mb-1">Penyidik (Instansi/Nama)</label>
                    <input type="text" name="penyidik" id="penyidik" value="{{ old('penyidik') }}" required class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600">
                    @error('penyidik')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label for="jaksa" class="block text-sm font-medium text-gray-700 mb-1">Jaksa Penuntut Umum</label>
                    <input type="text" name="jaksa" id="jaksa" value="{{ old('jaksa') }}" required class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600">
                    @error('jaksa')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
                
            </div>
            
            <div class="bg-yellow-50 text-yellow-800 text-sm p-4 rounded-md border border-yellow-200 mt-4">
                <strong>Catatan:</strong> Setelah disimpan, perkara akan otomatis masuk ke tahap <strong>SPDP</strong> dengan status awal <em>Menunggu Penerimaan SPDP</em>.
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-200 space-x-3">
                <a href="{{ route('perkara.index') }}" class="inline-flex h-9 items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600">
                    Batal
                </a>
                <button type="submit" class="inline-flex h-9 items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-indigo-700 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600">
                    Simpan Perkara
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
