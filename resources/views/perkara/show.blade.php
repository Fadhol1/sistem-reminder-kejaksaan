@extends('layouts.app')

@section('title', 'Detail Perkara')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <a href="{{ route('perkara.index') }}" class="text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            </a>
            <h3 class="text-xl font-semibold text-gray-900">Detail Perkara</h3>
        </div>
        @if(auth()->user()->isPidum())
        <div class="flex items-center space-x-2">
            <a href="{{ route('perkara.edit', $perkara->id) }}" class="inline-flex items-center justify-center rounded-md text-sm font-medium focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring bg-white border border-gray-300 shadow-sm hover:bg-gray-50 h-9 px-4 py-2">
                <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                </svg>
                Edit Perkara
            </a>
            
            <button type="button" onclick="openDeleteModal()" class="inline-flex items-center justify-center rounded-md text-sm font-medium focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring bg-white border border-red-300 text-red-600 shadow-sm hover:bg-red-50 h-9 px-4 py-2">
                <svg class="mr-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
                Hapus
            </button>
        </div>
        @endif
    </div>

    @if(session('success'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50" role="alert">
            {{ session('success') }}
        </div>
    @endif
    
    @if(session('error'))
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
            {{ session('error') }}
        </div>
    @endif
    
    @if($errors->any())
        <div class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50" role="alert">
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Kolom Kiri Keterangan Utama -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Data Perkara -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h4 class="font-medium text-gray-900">Informasi Perkara (PIDUM)</h4>
                </div>
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="block text-gray-500 mb-1">Nomor Perkara / SPDP</span>
                        <span class="font-semibold text-gray-900">{{ $perkara->nomor_perkara }}</span>
                    </div>
                    <div>
                        <span class="block text-gray-500 mb-1">Tanggal SPDP</span>
                        <span class="font-semibold text-gray-900">{{ $perkara->tanggal_spdp->format('d M Y') }}</span>
                    </div>
                    <div>
                        <span class="block text-gray-500 mb-1">Daftar Tersangka</span>
                        <ul class="font-medium text-gray-900 space-y-1">
                            @foreach($perkara->tersangkas as $tersangka)
                                <li>{{ $tersangka->nama }}</li>
                            @endforeach
                        </ul>
                    </div>
                    <div>
                        <span class="block text-gray-500 mb-1">Bidang / Satuan Kerja</span>
                        <span class="font-medium text-gray-900">{{ $perkara->bidang }} / {{ $perkara->satuan_kerja ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="block text-gray-500 mb-1">Penyidik</span>
                        <span class="font-medium text-gray-900">{{ $perkara->penyidik }}</span>
                    </div>
                    <div>
                        <span class="block text-gray-500 mb-1">Jaksa Penuntut Umum</span>
                        <ul class="font-medium text-gray-900 space-y-1">
                            @forelse($perkara->jaksas as $jaksaTerkait)
                                <li>{{ $jaksaTerkait->name }}</li>
                            @empty
                                <li>{{ $perkara->jaksa }}</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Riwayat/Timeline -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h4 class="font-medium text-gray-900">Riwayat Perjalanan Perkara (Audit Trail)</h4>
                </div>
                <div class="p-6">
                    <div class="relative border-l border-gray-200 ml-3">
                        @foreach($perkara->timelines as $timeline)
                        <div class="mb-6 ml-6">
                            <span class="absolute flex items-center justify-center w-6 h-6 bg-indigo-100 rounded-full -left-3 ring-4 ring-white">
                                <svg class="w-3 h-3 text-indigo-600" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M20 4a2 2 0 0 0-2-2h-2V1a1 1 0 0 0-2 0v1h-3V1a1 1 0 0 0-2 0v1H6V1a1 1 0 0 0-2 0v1H2a2 2 0 0 0-2 2v2h20V4ZM0 18a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8H0v10Zm5-8h10a1 1 0 0 1 0 2H5a1 1 0 0 1 0-2Z"/>
                                </svg>
                            </span>
                            <h5 class="flex items-center mb-1 text-sm font-semibold text-gray-900">
                                {{ $timeline->tahap }} 
                                <span class="bg-gray-100 text-gray-600 text-xs font-medium mr-2 px-2.5 py-0.5 rounded ml-3">
                                    {{ $timeline->status_kegiatan }}
                                </span>
                            </h5>
                            <time class="block mb-2 text-xs font-normal leading-none text-gray-400">
                                {{ $timeline->tanggal_kejadian->format('d M Y H:i') }} • Oleh: {{ $timeline->user->name ?? 'System' }}
                            </time>
                            @if($timeline->catatan)
                                <p class="text-sm font-normal text-gray-500">{{ $timeline->catatan }}</p>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>

        <!-- Kolom Kanan Status & Action -->
        <div class="space-y-6">
            
            <!-- Status Card -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h4 class="font-medium text-gray-900">Status Saat Ini</h4>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Tahap</p>
                        <span class="inline-block rounded-md bg-indigo-50 px-2.5 py-1 text-sm font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-700/10">{{ $perkara->tahap_saat_ini }}</span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Status</p>
                        <p class="font-semibold text-gray-900">{{ $perkara->status_saat_ini }}</p>
                    </div>

                    @if($perkara->activeReminders->count() > 0)
                        <div class="pt-4 border-t border-gray-100 mt-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide mb-2">Reminder Aktif (Deadline)</p>
                            @foreach($perkara->activeReminders as $reminder)
                                <div class="bg-gray-50 rounded p-3 mb-2 border-l-4 
                                    @if($reminder->status_urgensi === 'Aman') border-green-400 
                                    @elseif($reminder->status_urgensi === 'H-3') border-amber-400
                                    @else border-red-500 @endif">
                                    <p class="text-xs font-semibold text-gray-900">{{ $reminder->jenis_reminder }}</p>
                                    <p class="text-xs text-gray-600 mt-1">Due: {{ $reminder->deadline->format('d M Y') }}</p>
                                    <p class="text-[10px] uppercase font-bold mt-1 
                                        @if($reminder->status_urgensi === 'Aman') text-green-600 
                                        @elseif($reminder->status_urgensi === 'H-3') text-amber-600
                                        @else text-red-600 @endif">
                                        {{ $reminder->status_urgensi }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Catat Perkembangan Action Card -->
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h4 class="font-medium text-gray-900">Catat Perkembangan</h4>
                </div>
                <div class="p-6">
                    @if(count($availableActions) > 0)
                        <p class="text-sm text-gray-500 mb-4">Pilih aksi selanjutnya sesuai alur perkara:</p>
                        
                        <form action="{{ route('perkara.catatPerkembangan', $perkara->id) }}" method="POST" class="space-y-4">
                            @csrf
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Aksi</label>
                                <select name="action_id" required class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600">
                                    <option value="" disabled selected>-- Pilih Aksi --</option>
                                    @foreach($availableActions as $action)
                                        <option value="{{ $action['id'] }}">{{ $action['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                                <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" required class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan (Opsional)</label>
                                <textarea name="catatan" rows="2" class="flex w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600"></textarea>
                            </div>

                            <button type="submit" class="w-full inline-flex h-9 items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-indigo-700 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600">
                                Simpan Perkembangan
                            </button>
                        </form>
                    @else
                        <div class="text-center p-4">
                            <p class="text-sm text-gray-500">Tidak ada aksi lanjutan yang tersedia (Perkara Selesai atau status statis).</p>
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="relative z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-gray-900/60 transition-opacity backdrop-blur-sm" id="deleteModalBackdrop"></div>
    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-xl bg-white/70 backdrop-blur-xl border border-white/40 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                <div class="px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10 shadow-sm border border-red-200">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-base font-semibold leading-6 text-gray-900 drop-shadow-sm" id="modal-title">Hapus Perkara</h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-700">Apakah Anda yakin ingin menghapus perkara ini sepenuhnya beserta seluruh riwayat timeline dan remindernya? Tindakan ini tidak dapat dibatalkan.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="px-4 py-4 sm:flex sm:flex-row-reverse sm:px-6 border-t border-white/50 bg-white/30">
                    <form action="{{ route('perkara.destroy', $perkara->id) }}" method="POST" id="deleteForm">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex w-full justify-center rounded-md bg-red-600/90 hover:bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm sm:ml-3 sm:w-auto transition-colors">Hapus Perkara</button>
                    </form>
                    <button type="button" onclick="closeDeleteModal()" class="mt-3 inline-flex w-full justify-center rounded-md bg-white/60 hover:bg-white/90 px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300/50 sm:mt-0 sm:w-auto transition-colors">Batal</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const deleteModal = document.getElementById('deleteModal');
    
    function openDeleteModal() {
        deleteModal.classList.remove('hidden');
    }
    
    function closeDeleteModal() {
        deleteModal.classList.add('hidden');
    }
    
    // Close modal if user clicks outside of it
    document.getElementById('deleteModalBackdrop').addEventListener('click', closeDeleteModal);
</script>
@endsection
