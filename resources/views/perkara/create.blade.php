@extends('layouts.app')

@section('title', 'Tambah Perkara (PIDUM)')

@section('content')
<div class="space-y-6 max-w-3xl mx-auto">
    <div class="flex items-center space-x-3">
        <a href="{{ route('perkara.index') }}" class="text-gray-500 hover:text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6" />
            </svg>
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

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Daftar Tersangka</label>

                    <div id="tersangka-container" class="space-y-2">
                        <div class="tersangka-row flex gap-2 items-center">
                            <input type="text" name="tersangkas[0][nama]" value="" placeholder="Nama tersangka" required class="flex h-10 flex-1 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600">
                            <button type="button" onclick="removeRow(this)" class="h-10 w-10 shrink-0 rounded-md border border-red-300 text-red-500 hover:bg-red-50 flex items-center justify-center">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <button type="button" onclick="addRow()" class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-700">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                        Tambah Tersangka
                    </button>
                </div>

                <div>
                    <label for="penyidik" class="block text-sm font-medium text-gray-700 mb-1">Penyidik</label>
                    <select name="penyidik" id="penyidik" required class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600">
                        <option value="" disabled {{ old('penyidik') ? '' : 'selected' }}>-- Pilih Penyidik --</option>
                        @foreach($penyidiks as $p)
                            <option value="{{ $p->name }}" {{ old('penyidik') == $p->name ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                    @error('penyidik')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jaksa Penuntut Umum (Pilih Minimal 1)</label>
                    
                    <!-- Fitur Search Jaksa -->
                    <div class="relative mb-2">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg aria-hidden="true" class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" id="searchJaksaInput" onkeyup="filterJaksa()" placeholder="Cari nama jaksa..." class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-md focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-9 p-2">
                    </div>

                    <div id="jaksaListContainer" class="max-h-48 overflow-y-auto p-3 border border-gray-300 rounded-md bg-white space-y-3">
                        @foreach($jaksas as $j)
                            <div class="flex items-center jaksa-item">
                                <input id="jaksa_{{ $j->id }}" name="jaksas[]" type="checkbox" value="{{ $j->id }}" 
                                    {{ (is_array(old('jaksas')) && in_array($j->id, old('jaksas'))) ? 'checked' : '' }}
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                                <label for="jaksa_{{ $j->id }}" class="ml-3 block text-sm font-medium text-gray-700 cursor-pointer jaksa-name">
                                    {{ $j->name }}
                                </label>
                            </div>
                        @endforeach
                        
                        <!-- Pesan 'Tidak Ditemukan' -->
                        <div id="jaksaEmptyMessage" class="hidden text-sm text-gray-500 text-center py-2">
                            Jaksa tidak ditemukan.
                        </div>
                    </div>
                    @error('jaksas')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            <div class="bg-yellow-50 text-yellow-800 text-sm p-4 rounded-md border border-yellow-200 mt-4">
                <strong>Catatan:</strong> Setelah disimpan, perkara akan otomatis masuk ke tahap <strong>Koordinasi Awal</strong> dengan status awal <em>Menunggu Koordinasi</em>. Tanggal SPDP di atas tetap dibutuhkan untuk perhitungan batas SLA 30 hari Penyerahan Berkas.
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

<script>
    let rowIndex = document.querySelectorAll('.tersangka-row').length;

    function addRow() {
        const container = document.getElementById('tersangka-container');
        const html = '<div class="tersangka-row flex gap-2 items-center">' +
            '<input type="text" name="tersangkas[' + rowIndex + '][nama]" value="" placeholder="Nama tersangka" required class="flex h-10 flex-1 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600">' +
            '<button type="button" onclick="removeRow(this)" class="h-10 w-10 shrink-0 rounded-md border border-red-300 text-red-500 hover:bg-red-50 flex items-center justify-center">' +
            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>' +
            '</button></div>';
        container.insertAdjacentHTML('beforeend', html);
        rowIndex++;
        container.lastElementChild.querySelector('input').focus();
    }

    function removeRow(btn) {
        const container = document.getElementById('tersangka-container');
        if (container.children.length > 1) {
            btn.closest('.tersangka-row').remove();
        } else {
            alert('Minimal harus ada satu tersangka.');
        }
    }

    function filterJaksa() {
        const input = document.getElementById('searchJaksaInput');
        const filter = input.value.toLowerCase();
        const container = document.getElementById('jaksaListContainer');
        const items = container.getElementsByClassName('jaksa-item');
        const emptyMessage = document.getElementById('jaksaEmptyMessage');
        let visibleCount = 0;

        for (let i = 0; i < items.length; i++) {
            const label = items[i].querySelector('.jaksa-name');
            const txtValue = label.textContent || label.innerText;
            if (txtValue.toLowerCase().indexOf(filter) > -1) {
                items[i].classList.remove('hidden');
                // Paksa flex display by tailwind default, though remove hidden should suffice for flex wrappers if we use inline logic.
                // Using non-inline styles via tailwind utility classes is cleaner.
                items[i].style.display = "";
                visibleCount++;
            } else {
                items[i].style.display = "none";
            }
        }

        if (visibleCount === 0) {
            emptyMessage.classList.remove('hidden');
        } else {
            emptyMessage.classList.add('hidden');
        }
    }
</script>
@endsection