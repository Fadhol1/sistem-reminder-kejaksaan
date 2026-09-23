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
                        <span class="block text-gray-500 mb-1">Nama Tersangka</span>
                        <span class="font-medium text-gray-900">{{ $perkara->nama_tersangka }}</span>
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
                        <span class="font-medium text-gray-900">{{ $perkara->jaksa }}</span>
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
@endsection
