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

            <div class="grid grid-cols-1 gap-6">

                <div>
                    <label for="nomor_perkara" class="block text-sm font-medium text-gray-700 mb-1">Nomor Perkara / SPDP</label>
                    <input type="text" name="nomor_perkara" id="nomor_perkara" value="{{ old('nomor_perkara', $perkara->nomor_perkara) }}" required class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600 @error('nomor_perkara') border-red-500 @enderror">
                    @error('nomor_perkara')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Daftar Tersangka</label>

                    <div id="tersangka-container" class="space-y-2">
                        @foreach(old('tersangkas', $perkara->tersangkas) as $index => $tersangka)
                        <div class="tersangka-row flex gap-2 items-center">
                            <input type="text" name="tersangkas[{{ $index }}][nama]" value="{{ is_array($tersangka) ? ($tersangka['nama'] ?? '') : $tersangka->nama }}" placeholder="Nama tersangka" required class="flex h-10 flex-1 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600">
                            <button type="button" onclick="removeRow(this)" class="h-10 w-10 shrink-0 rounded-md border border-red-300 text-red-500 hover:bg-red-50 flex items-center justify-center">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                            </button>
                        </div>
                        @endforeach
                    </div>

                    <button type="button" onclick="addRow()" class="mt-2 inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-700">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                        Tambah Tersangka
                    </button>
                    @error('tersangkas')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            <div class="flex justify-end pt-4 border-t border-gray-200 space-x-3 mt-6">
                <a href="{{ route('perkara.show', $perkara->id) }}" class="inline-flex h-9 items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600">
                    Batal
                </a>
                <button type="submit" class="inline-flex h-9 items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-indigo-700 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let rowIndex = {{ count(old('tersangkas', $perkara->tersangkas)) }};

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
</script>
@endsection
