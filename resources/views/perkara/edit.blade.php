@extends('layouts.app')

@section('title', 'Edit Perkara')

@section('content')
<div class="space-y-6 max-w-3xl mx-auto">
    <div class="flex items-center space-x-3">
        <a href="{{ route('perkara.show', $perkara->id) }}" class="text-gray-500 hover:text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6" />
            </svg>
        </a>
        <h3 class="text-xl font-semibold text-gray-900">Edit Perkara</h3>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden p-6">
        <form action="{{ route('perkara.update', $perkara->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div class="col-span-1 md:col-span-2">
                    <label for="nomor_perkara" class="block text-sm font-medium text-gray-700 mb-1">Nomor Perkara / SPDP</label>
                    <input type="text" name="nomor_perkara" id="nomor_perkara" value="{{ old('nomor_perkara', $perkara->nomor_perkara) }}" required class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus:border-red-600 focus-visible:ring-2 focus-visible:ring-red-600/20 @error('nomor_perkara') border-red-500 @enderror">
                    @error('nomor_perkara')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="col-span-1 md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Daftar Penyidik / Instansi Asal</label>

                    <div id="penyidik-container" class="space-y-2">
                        @php
                            $penyidikSource = old('penyidiks');
                            if ($penyidikSource === null) {
                                if ($perkara->penyidiks && $perkara->penyidiks->count() > 0) {
                                    $penyidikSource = $perkara->penyidiks;
                                } elseif (!empty($perkara->penyidik)) {
                                    $penyidikSource = [['nama' => $perkara->penyidik]];
                                } else {
                                    $penyidikSource = [['nama' => '']];
                                }
                            }
                        @endphp
                        @foreach($penyidikSource as $index => $penyidik)
                        <div class="penyidik-row flex gap-2 items-center">
                            <input type="text" name="penyidiks[{{ $index }}][nama]" value="{{ is_array($penyidik) ? ($penyidik['nama'] ?? '') : $penyidik->nama }}" placeholder="Contoh: IPTU Bambang Wijaya / Polres OKU" required class="flex h-10 flex-1 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus:border-red-600 focus-visible:ring-2 focus-visible:ring-red-600/20">
                            <button type="button" onclick="removePenyidikRow(this)" class="h-10 w-10 shrink-0 rounded-md border border-red-300 text-red-500 hover:bg-red-50 flex items-center justify-center">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            </button>
                        </div>
                        @endforeach
                    </div>

                    <button type="button" onclick="addPenyidikRow()" class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-red-700 hover:text-red-900">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                        Tambah Penyidik
                    </button>
                    @error('penyidiks')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="col-span-1 md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Daftar Tersangka</label>

                    <div id="tersangka-container" class="space-y-2">
                        @foreach(old('tersangkas', $perkara->tersangkas) as $index => $tersangka)
                        <div class="tersangka-row flex gap-2 items-center">
                            <input type="text" name="tersangkas[{{ $index }}][nama]" value="{{ is_array($tersangka) ? ($tersangka['nama'] ?? '') : $tersangka->nama }}" placeholder="Nama tersangka" required class="flex h-10 flex-1 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus:border-red-600 focus-visible:ring-2 focus-visible:ring-red-600/20">
                            <button type="button" onclick="removeTersangkaRow(this)" class="h-10 w-10 shrink-0 rounded-md border border-red-300 text-red-500 hover:bg-red-50 flex items-center justify-center">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            </button>
                        </div>
                        @endforeach
                    </div>

                    <button type="button" onclick="addTersangkaRow()" class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-red-700 hover:text-red-900">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                        Tambah Tersangka
                    </button>
                    @error('tersangkas')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="col-span-1 md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jaksa Penuntut Umum (Pilih Minimal 1)</label>
                    
                    <!-- Fitur Search Jaksa -->
                    <div class="relative mb-2">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg aria-hidden="true" class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" id="searchJaksaInput" onkeyup="filterJaksa()" placeholder="Cari nama jaksa..." class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-md focus:ring-red-600 focus:border-red-600 block w-full pl-9 p-2">
                    </div>

                    <div id="jaksaListContainer" class="max-h-52 overflow-y-auto p-3 border border-gray-300 rounded-md bg-white space-y-3">
                        @foreach($jaksas as $j)
                            @php
                                $isChecked = (is_array(old('jaksas')) && in_array($j->id, old('jaksas'))) || 
                                             (!old('jaksas') && in_array($j->id, $selectedJaksaIds));
                            @endphp
                            <div class="flex items-center jaksa-item">
                                <input id="jaksa_{{ $j->id }}" name="jaksas[]" type="checkbox" value="{{ $j->id }}" 
                                    {{ $isChecked ? 'checked' : '' }}
                                    class="h-4 w-4 rounded border-gray-300 text-red-700 focus:ring-red-600">
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

            <div class="flex justify-end pt-4 border-t border-gray-200 space-x-3 mt-6">
                <a href="{{ route('perkara.show', $perkara->id) }}" class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                    Batal
                </a>
                <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-gradient-to-r from-[#881337] via-[#991b1b] to-[#7f1d1d] hover:from-[#700d1a] hover:via-[#881337] hover:to-[#5c0b15] px-4 py-2 text-sm font-semibold text-white shadow-sm transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let tersangkaRowIndex = {{ count(old('tersangkas', $perkara->tersangkas)) }};

    function addTersangkaRow() {
        const container = document.getElementById('tersangka-container');
        const html = '<div class="tersangka-row flex gap-2 items-center">' +
            '<input type="text" name="tersangkas[' + tersangkaRowIndex + '][nama]" value="" placeholder="Nama tersangka" required class="flex h-10 flex-1 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus:border-red-600 focus-visible:ring-2 focus-visible:ring-red-600/20">' +
            '<button type="button" onclick="removeTersangkaRow(this)" class="h-10 w-10 shrink-0 rounded-md border border-red-300 text-red-500 hover:bg-red-50 flex items-center justify-center">' +
            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>' +
            '</button></div>';
        container.insertAdjacentHTML('beforeend', html);
        tersangkaRowIndex++;
        container.lastElementChild.querySelector('input').focus();
    }

    function removeTersangkaRow(btn) {
        const container = document.getElementById('tersangka-container');
        if (container.querySelectorAll('.tersangka-row').length > 1) {
            btn.closest('.tersangka-row').remove();
        } else {
            alert('Minimal harus ada satu tersangka.');
        }
    }

    function addRow() { addTersangkaRow(); }
    function removeRow(btn) { removeTersangkaRow(btn); }

    @php
        $initPenyidikCount = count($penyidikSource ?? []);
    @endphp
    let penyidikRowIndex = {{ $initPenyidikCount > 0 ? $initPenyidikCount : 1 }};

    function addPenyidikRow() {
        const container = document.getElementById('penyidik-container');
        const html = '<div class="penyidik-row flex gap-2 items-center">' +
            '<input type="text" name="penyidiks[' + penyidikRowIndex + '][nama]" value="" placeholder="Contoh: IPTU Bambang Wijaya / Polres OKU" required class="flex h-10 flex-1 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus:border-red-600 focus-visible:ring-2 focus-visible:ring-red-600/20">' +
            '<button type="button" onclick="removePenyidikRow(this)" class="h-10 w-10 shrink-0 rounded-md border border-red-300 text-red-500 hover:bg-red-50 flex items-center justify-center">' +
            '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>' +
            '</button></div>';
        container.insertAdjacentHTML('beforeend', html);
        penyidikRowIndex++;
        container.lastElementChild.querySelector('input').focus();
    }

    function removePenyidikRow(btn) {
        const container = document.getElementById('penyidik-container');
        if (container.querySelectorAll('.penyidik-row').length > 1) {
            btn.closest('.penyidik-row').remove();
        } else {
            alert('Minimal harus ada satu penyidik.');
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
            if (label) {
                const txtValue = label.textContent || label.innerText;
                if (txtValue.toLowerCase().indexOf(filter) > -1) {
                    items[i].style.display = 'flex';
                    visibleCount++;
                } else {
                    items[i].style.display = 'none';
                }
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
