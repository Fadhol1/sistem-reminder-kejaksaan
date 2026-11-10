@extends('layouts.app')

@section('title', 'Detail Perkara')

@section('content')
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="{{ route('perkara.index') }}" class="text-gray-500 hover:text-gray-700">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6" />
                    </svg>
                </a>
                <h3 class="text-xl font-semibold text-gray-900">Detail Perkara</h3>
            </div>
            @if(auth()->user()->isPidum())
                <div class="flex items-center space-x-2">
                    @if($perkara->isKoordinasiAwalOverdue())
                        <span
                            class="inline-flex items-center justify-center rounded-lg text-sm font-medium bg-gray-100 border border-gray-200 text-gray-400 h-9 px-3.5 py-2 cursor-not-allowed select-none"
                            title="Perkara pada tahap Koordinasi Awal yang overdue terkunci dan tidak dapat diubah">
                            <svg class="mr-1.5 h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                            </svg>
                            Terkunci (Overdue)
                        </span>
                    @else
                        <a href="{{ route('perkara.edit', $perkara->id) }}"
                            class="inline-flex items-center justify-center rounded-lg text-sm font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-600 bg-amber-50 border border-amber-300 text-amber-900 shadow-2xs hover:bg-amber-100 h-9 px-3.5 py-2 transition-colors"
                            title="Ubah Data Perkara">
                            <svg class="mr-1.5 h-4 w-4 text-amber-700" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" />
                            </svg>
                            Edit Perkara
                        </a>
                    @endif
                    <button type="button" onclick="openDeleteModal()"
                        class="inline-flex items-center justify-center rounded-lg text-sm font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600 bg-red-50 border border-red-200 text-red-700 shadow-2xs hover:bg-red-100 h-9 px-3.5 py-2 transition-colors">
                        <svg class="mr-1.5 h-4 w-4 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                        Hapus Perkara
                    </button>
                </div>
            @endif
        </div>

        @if($perkara->isKoordinasiAwalOverdue())
            <div class="rounded-xl border border-rose-200 bg-rose-50/70 p-4 shadow-2xs">
                <div class="flex items-start gap-3">
                    <div class="rounded-lg bg-rose-100 p-2 text-rose-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-sm font-bold text-rose-900">Perkara Terkunci (Koordinasi Awal Overdue)</h4>
                        <p class="mt-0.5 text-xs text-rose-700">
                            Batas waktu SLA 3 hari untuk tahap Koordinasi Awal telah terlampaui. Data perkara serta pencatatan
                            perkembangan telah dikunci secara otomatis oleh sistem demi menjaga integritas riwayat dan kepatuhan
                            SOP penanganan perkara.
                        </p>
                    </div>
                </div>
            </div>
        @endif

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
                            <span class="font-medium text-gray-900">{{ $perkara->bidang }} /
                                {{ $perkara->satuan_kerja ?? '-' }}</span>
                        </div>
                        <div>
                            <span class="block text-gray-500 mb-1">Daftar Penyidik</span>
                            <ul class="font-medium text-gray-900 space-y-1">
                                @if($perkara->penyidiks && $perkara->penyidiks->count() > 0)
                                    @foreach($perkara->penyidiks as $penyidikItem)
                                        <li>{{ $penyidikItem->nama }}</li>
                                    @endforeach
                                @elseif(!empty($perkara->penyidik))
                                    <li>{{ $perkara->penyidik }}</li>
                                @else
                                    <li>-</li>
                                @endif
                            </ul>
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
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                        <h4 class="font-semibold text-gray-900 flex items-center gap-2">
                            <svg class="w-4 h-4 text-red-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Riwayat Perjalanan Perkara (Audit Trail)
                        </h4>
                        <span class="text-xs font-medium text-gray-500">{{ $perkara->timelines->count() }} peristiwa</span>
                    </div>
                    <div class="p-6">
                        <div class="relative border-l-2 border-gray-200 ml-3 space-y-6">
                            @forelse($perkara->timelines as $timeline)
                                @php
                                    $isSystem = ($timeline->actor_type === 'SYSTEM');
                                    $isReverted = (bool) $timeline->is_reverted;
                                    $isKoreksi = str_starts_with($timeline->status_kegiatan, '[KOREKSI');
                                    $tahapBg = match ($timeline->tahap) {
                                        'Koordinasi Awal' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'Monitoring Penyidikan' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'P-24' => 'bg-purple-50 text-purple-700 border-purple-200',
                                        'P-19' => 'bg-orange-50 text-orange-700 border-orange-200',
                                        'P-21' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'Tahap II' => 'bg-teal-50 text-teal-700 border-teal-200',
                                        default => 'bg-gray-100 text-gray-700 border-gray-200',
                                    };
                                @endphp
                                <div class="relative pl-6 group">
                                    <!-- Node Icon -->
                                    <div class="absolute -left-[13px] top-1 flex items-center justify-center">
                                        @if($isReverted)
                                            <span
                                                class="flex h-6 w-6 items-center justify-center rounded-full bg-rose-500 text-white ring-4 ring-white shadow-2xs"
                                                title="Aksi telah dibatalkan (Koreksi)">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2.4">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </span>
                                        @elseif($isKoreksi)
                                            <span
                                                class="flex h-6 w-6 items-center justify-center rounded-full bg-amber-500 text-white ring-4 ring-white shadow-2xs"
                                                title="Koreksi Administrasi">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2.4">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                                </svg>
                                            </span>
                                        @elseif($isSystem)
                                            <span
                                                class="flex h-6 w-6 items-center justify-center rounded-full bg-purple-600 text-white ring-4 ring-white shadow-2xs">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2.2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                                                </svg>
                                            </span>
                                        @else
                                            <span
                                                class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-900 text-white ring-4 ring-white shadow-2xs group-hover:bg-red-700 transition">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                    stroke-width="2.2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M4.5 12.75l6 6 9-13.5" />
                                                </svg>
                                            </span>
                                        @endif
                                    </div>

                                    <div
                                        class="rounded-lg p-3.5 border transition @if($isReverted) bg-rose-50/40 border-rose-200/80 opacity-80 @elseif($isKoreksi) bg-amber-50/60 border-amber-300 @else bg-gray-50/70 hover:bg-white border-gray-200 @endif">
                                        <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border {{ $tahapBg }}">
                                                    {{ $timeline->tahap }}
                                                </span>
                                                @if($isReverted)
                                                    <span
                                                        class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                                                        DIBATALKAN / DIKOREKSI
                                                    </span>
                                                @elseif($isKoreksi)
                                                    <span
                                                        class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                                        KOREKSI ADMINISTRASI
                                                    </span>
                                                @elseif($isSystem)
                                                    <span
                                                        class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-purple-100 text-purple-800 border border-purple-200">
                                                        <svg class="w-3 h-3 text-purple-600" fill="none" viewBox="0 0 24 24"
                                                            stroke="currentColor" stroke-width="2">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                                                        </svg>
                                                        Otomasi Sistem
                                                    </span>
                                                @else
                                                    <span class="text-xs text-gray-500 font-medium">
                                                        Oleh: <strong
                                                            class="text-gray-700">{{ $timeline->user->name ?? 'Petugas' }}</strong>
                                                    </span>
                                                @endif
                                            </div>
                                            <time class="text-xs text-gray-400 font-medium">
                                                {{ $timeline->tanggal_kejadian->translatedFormat('d M Y') }} •
                                                {{ $timeline->created_at->timezone(config('app.timezone', 'Asia/Jakarta'))->format('H:i') }}
                                                WIB
                                            </time>
                                        </div>

                                        <h5
                                            class="text-sm font-bold @if($isReverted) text-gray-400 line-through @else text-gray-900 @endif">
                                            {{ $timeline->status_kegiatan }}
                                        </h5>

                                        @if($timeline->catatan)
                                            <div
                                                class="mt-2 text-xs text-gray-600 italic bg-white p-2.5 rounded border border-gray-100">
                                                "{{ $timeline->catatan }}"
                                            </div>
                                        @endif

                                        @if($isReverted)
                                            <div
                                                class="mt-2 text-xs bg-rose-50 border border-rose-200 rounded p-2.5 text-rose-900 flex items-start gap-2">
                                                <svg class="w-4 h-4 text-rose-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                                </svg>
                                                <div>
                                                    <span class="font-bold">Dibatalkan oleh
                                                        {{ $timeline->reverter->name ?? 'Admin PIDUM' }}</span>
                                                    @if($timeline->revert_reason)
                                                        <p class="mt-0.5 text-rose-800 italic">"{{ $timeline->revert_reason }}"</p>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-400 py-4 text-center">Belum ada riwayat tercatat.</p>
                            @endforelse
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
                            <span
                                class="inline-block rounded-md bg-red-50 px-2.5 py-1 text-sm font-semibold text-red-800 ring-1 ring-inset ring-red-700/20">{{ $perkara->tahap_saat_ini }}</span>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Status</p>
                            <p class="font-semibold text-gray-900">{{ $perkara->status_saat_ini }}</p>
                        </div>

                        @if(str_contains($perkara->status_saat_ini, 'FORM-3'))
                            <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700">
                                <p class="font-semibold text-slate-900 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Alur Monitoring Selesai (Arsip)
                                </p>
                                <p class="mt-1 text-slate-600">Berkas SPDP telah dikembalikan kepada Penyidik dan administrasi
                                    perkara disimpan ke arsip. Seluruh jadwal reminder dinonaktifkan.</p>
                            </div>
                        @elseif(str_contains($perkara->status_saat_ini, 'P-20'))
                            <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700">
                                <p class="font-semibold text-slate-900 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    SPDP Dikembalikan (P-20 / Arsip)
                                </p>
                                <p class="mt-1 text-slate-600">Waktu penyidikan tambahan oleh Penyidik telah habis (>14 hari
                                    pasca P-19). Berkas SPDP dikembalikan kepada Penyidik dan perkara disimpan ke arsip. Seluruh
                                    jadwal reminder dinonaktifkan.</p>
                            </div>
                        @elseif(str_contains($perkara->status_saat_ini, 'FORM-7'))
                            <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700">
                                <p class="font-semibold text-slate-900 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Berkas & SPDP Dikembalikan (FORM-7 / Arsip)
                                </p>
                                <p class="mt-1 text-slate-600">Tenggat penyerahan tanggung jawab Tersangka dan Barang Bukti
                                    (Tahap II) terlewati (>14 hari pasca P-21). Berkas Perkara dan SPDP dikembalikan kepada
                                    Penyidik dan diarsipkan. Seluruh jadwal reminder dinonaktifkan.</p>
                            </div>
                        @elseif($perkara->status_saat_ini === 'Tahap II Selesai')
                            <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-xs text-emerald-800">
                                <p class="font-semibold text-emerald-900 flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Perkara Selesai (Tahap II)
                                </p>
                                <p class="mt-1 text-emerald-700">Penyerahan Tersangka dan Barang Bukti telah selesai
                                    dilaksanakan secara lengkap.</p>
                            </div>
                        @endif

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
                    <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                        <h4 class="font-medium text-gray-900">Catat Perkembangan</h4>
                        @if($perkara->isKoordinasiAwalOverdue())
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                <svg class="w-3.5 h-3.5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                                Terkunci (Overdue)
                            </span>
                        @elseif(str_contains($perkara->status_saat_ini, 'FORM-3') || str_contains($perkara->status_saat_ini, 'P-20') || str_contains($perkara->status_saat_ini, 'FORM-7'))
                            <span
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                SPDP Dikembalikan
                            </span>
                        @elseif($perkara->status_saat_ini === 'Tahap II Selesai')
                            <span
                                class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                Tahap II Selesai
                            </span>
                        @endif
                    </div>
                    <div class="p-6">
                        @if($perkara->isKoordinasiAwalOverdue())
                            <div
                                class="rounded-xl bg-gradient-to-br from-rose-50/80 via-rose-50/50 to-red-50/30 border border-rose-200 p-5 text-center shadow-2xs">
                                <div
                                    class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-3 shadow-2xs border border-rose-200">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                    </svg>
                                </div>
                                <h5 class="text-sm font-bold text-rose-900 mb-1.5">Pencatatan Perkembangan Dinonaktifkan</h5>
                                <p class="text-xs text-rose-700 leading-relaxed max-w-sm mx-auto mb-3">
                                    Perkara pada tahap <strong>Koordinasi Awal</strong> telah melewati batas waktu SLA 3 hari
                                    (Overdue). Aksi perkembangan telah dikunci oleh sistem sesuai SOP Kejaksaan.
                                </p>
                                <div
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md bg-white border border-rose-200 text-xs font-medium text-rose-800 shadow-2xs">
                                    <svg class="w-3.5 h-3.5 text-rose-600" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Status: Overdue / Tindak Lanjut Form-1B & Form-1C
                                </div>
                            </div>
                        @elseif(count($availableActions) > 0)
                            <p class="text-sm text-gray-500 mb-4">Pilih aksi selanjutnya sesuai alur perkara:</p>

                            <form action="{{ route('perkara.catatPerkembangan', $perkara->id) }}" method="POST"
                                class="space-y-4">
                                @csrf

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Aksi</label>
                                    <select name="action_id" required
                                        class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus:border-red-600 focus-visible:ring-2 focus-visible:ring-red-600/20">
                                        <option value="" disabled selected>-- Pilih Aksi --</option>
                                        @foreach($availableActions as $action)
                                            <option value="{{ $action['id'] }}">{{ $action['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                                    <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" required
                                        class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus:border-red-600 focus-visible:ring-2 focus-visible:ring-red-600/20">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan
                                        (Opsional)</label>
                                    <textarea name="catatan" rows="2"
                                        class="flex w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus:border-red-600 focus-visible:ring-2 focus-visible:ring-red-600/20"></textarea>
                                </div>

                                <button type="submit"
                                    class="w-full inline-flex h-10 items-center justify-center rounded-lg bg-gradient-to-r from-[#881337] via-[#991b1b] to-[#7f1d1d] hover:from-[#700d1a] hover:via-[#881337] hover:to-[#5c0b15] px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600">
                                    Simpan Perkembangan
                                </button>
                            </form>
                        @elseif(str_contains($perkara->status_saat_ini, 'FORM-3') || str_contains($perkara->status_saat_ini, 'P-20') || str_contains($perkara->status_saat_ini, 'FORM-7'))
                            <div class="rounded-xl bg-slate-50 border border-slate-200 p-5 text-center shadow-2xs">
                                <div
                                    class="w-12 h-12 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center mx-auto mb-3 shadow-2xs border border-slate-200">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h5 class="text-sm font-bold text-slate-900 mb-1.5">Pencatatan Perkembangan Dinonaktifkan</h5>
                                <p class="text-xs text-slate-600 leading-relaxed max-w-sm mx-auto mb-3">
                                    Berkas SPDP / Berkas Perkara telah dikembalikan kepada Penyidik. Administrasi penanganan
                                    perkara pada sistem telah selesai sehingga pencatatan perkembangan dinonaktifkan.
                                </p>
                                <div
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md bg-white border border-slate-200 text-xs font-medium text-slate-700 shadow-2xs">
                                    Status: {{ $perkara->status_saat_ini }}
                                </div>
                            </div>
                        @elseif($perkara->status_saat_ini === 'Tahap II Selesai')
                            <div class="rounded-xl bg-emerald-50/80 border border-emerald-200 p-5 text-center shadow-2xs">
                                <div
                                    class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-3 shadow-2xs border border-emerald-200">
                                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h5 class="text-sm font-bold text-emerald-900 mb-1.5">Perkara Selesai (Tahap II)</h5>
                                <p class="text-xs text-emerald-700 leading-relaxed max-w-sm mx-auto mb-3">
                                    Pelaksanaan Tahap II telah selesai dilaksanakan. Administrasi perkara beralih ke
                                    administrasi penuntutan / persidangan.
                                </p>
                                <div
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md bg-white border border-emerald-200 text-xs font-medium text-emerald-800 shadow-2xs">
                                    Status: Tahap II Selesai
                                </div>
                            </div>
                        @else
                            <div class="text-center p-4">
                                <p class="text-sm text-gray-500">Tidak ada aksi lanjutan yang tersedia (Perkara Selesai atau
                                    status statis).</p>
                            </div>
                        @endif

                        @if(isset($lastUndoableAction) && $lastUndoableAction && auth()->user()->isPidum())
                            <div class="mt-5 pt-4 border-t border-gray-200">
                                <div class="bg-amber-50/80 border border-amber-200 rounded-lg p-3.5 shadow-2xs">
                                    <div class="flex items-center justify-between gap-2 mb-1.5">
                                        <span class="text-xs font-bold text-amber-950 flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-amber-700" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                            </svg>
                                            Koreksi Langkah
                                        </span>
                                        <button type="button" onclick="openUndoModal()"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-bold text-amber-900 bg-amber-100 hover:bg-amber-200 border border-amber-300 rounded-md transition shadow-2xs">
                                            Batalkan Langkah Terakhir
                                        </button>
                                    </div>
                                    <p class="text-[11px] text-amber-800 leading-relaxed">
                                        Salah input perkembangan? Anda dapat membatalkan aksi terakhir: <strong
                                            class="font-semibold text-amber-950">"{{ $lastUndoableAction->status_kegiatan }}"</strong>.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                @if(app()->environment('local') || config('app.debug'))
                    @php
                        $isArchivedDev = str_contains($perkara->status_saat_ini, 'FORM-3') ||
                            str_contains($perkara->status_saat_ini, 'P-20') ||
                            str_contains($perkara->status_saat_ini, 'FORM-7') ||
                            $perkara->status_saat_ini === 'Tahap II Selesai';
                        $devActiveReminder = $perkara->activeReminders->first();
                    @endphp
                    <!-- Dev / Sandbox SLA Simulator Card (Collapsible) -->
                    <details class="group bg-white rounded-xl border border-amber-300 shadow-2xs overflow-hidden">
                        <summary
                            class="px-5 py-3.5 bg-amber-50/80 border-b border-amber-200 flex items-center justify-between cursor-pointer select-none hover:bg-amber-100/70 transition-colors list-none">
                            <div class="flex items-center gap-2">
                                <span
                                    class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-600 text-white tracking-wider uppercase">DEV
                                    TOOL</span>
                                <h4 class="font-bold text-xs sm:text-sm text-amber-950">Simulator Waktu & SLA Engine</h4>
                            </div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="text-[10px] font-semibold text-amber-800 bg-amber-200/90 px-2 py-0.5 rounded-full">Sandbox</span>
                                <svg class="w-4 h-4 text-amber-700 transition-transform duration-200 group-open:rotate-180"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>
                            </div>
                        </summary>

                        <div class="p-5 space-y-4 bg-gradient-to-br from-amber-50/40 via-white to-orange-50/30">
                            <!-- Contextual SLA Target Info -->
                            @if($perkara->tahap_saat_ini === \App\Services\PerkaraWorkflowService::TAHAP_P19)
                                <div
                                    class="p-3 bg-orange-100/70 border border-orange-200 rounded-lg text-xs text-orange-950 space-y-1">
                                    <div class="flex items-center gap-1.5 font-bold text-orange-900">
                                        <span
                                            class="px-1.5 py-0.5 bg-orange-600 text-white rounded text-[10px] uppercase font-bold tracking-wider">Uji
                                            SLA 14 Hari P-19</span>
                                        <span>Pemenuhan Petunjuk Jaksa</span>
                                    </div>
                                    <p class="text-orange-900/90 leading-relaxed text-[11px]">
                                        Penyidik wajib memenuhi petunjuk jaksa maksimal 14 hari. Jika melewati batas waktu (>14
                                        hari), <strong>SLA Engine otomatis menerbitkan P-20</strong> (Pengembalian SPDP),
                                        mengarsipkan perkara, dan menutup seluruh reminder.
                                    </p>
                                </div>
                            @elseif($perkara->tahap_saat_ini === \App\Services\PerkaraWorkflowService::TAHAP_P21)
                                <div
                                    class="p-3 bg-emerald-100/70 border border-emerald-200 rounded-lg text-xs text-emerald-950 space-y-1">
                                    <div class="flex items-center gap-1.5 font-bold text-emerald-900">
                                        <span
                                            class="px-1.5 py-0.5 bg-emerald-600 text-white rounded text-[10px] uppercase font-bold tracking-wider">Uji
                                            SLA 14 Hari P-21</span>
                                        <span>Penyerahan Tanggung Jawab Tahap II</span>
                                    </div>
                                    <p class="text-emerald-900/90 leading-relaxed text-[11px]">
                                        Penyidik wajib menyerahkan Tersangka & Barang Bukti maksimal 14 hari pasca P-21. Jika
                                        melewati batas waktu (>14 hari), <strong>SLA Engine otomatis menerbitkan SOP FORM-7</strong>
                                        (Pengembalian Berkas & SPDP ke Penyidik), mengarsipkan perkara, dan menutup seluruh
                                        reminder.
                                    </p>
                                </div>
                            @elseif($perkara->tahap_saat_ini === \App\Services\PerkaraWorkflowService::TAHAP_MONITORING)
                                <div
                                    class="p-3 bg-amber-100/70 border border-amber-200 rounded-lg text-xs text-amber-950 space-y-1">
                                    <div class="flex items-center gap-1.5 font-bold text-amber-900">
                                        <span
                                            class="px-1.5 py-0.5 bg-amber-600 text-white rounded text-[10px] uppercase font-bold tracking-wider">Uji
                                            SLA Siklus 30 Hari</span>
                                        <span>Monitoring Penyidikan</span>
                                    </div>
                                    <p class="text-amber-900/90 leading-relaxed text-[11px]">
                                        SPDP > 30 hari tanpa berkas ➔ <strong>P-17</strong> ➔ > 30 hari ➔ <strong>SOP
                                            FORM-2</strong> ➔ > 30 hari ➔ <strong>SOP FORM-3 (Arsip & Tutup Reminder)</strong>.
                                    </p>
                                </div>
                            @elseif($perkara->tahap_saat_ini === \App\Services\PerkaraWorkflowService::TAHAP_KOORDINASI)
                                <div class="p-3 bg-blue-100/70 border border-blue-200 rounded-lg text-xs text-blue-950 space-y-1">
                                    <div class="flex items-center gap-1.5 font-bold text-blue-900">
                                        <span
                                            class="px-1.5 py-0.5 bg-blue-600 text-white rounded text-[10px] uppercase font-bold tracking-wider">Uji
                                            SLA 3 Hari</span>
                                        <span>Koordinasi Awal</span>
                                    </div>
                                    <p class="text-blue-900/90 leading-relaxed text-[11px]">
                                        Batas waktu koordinasi awal adalah 3 hari. Jika lewat, sistem mencatat tindak lanjut Laporan
                                        SOP Form-1B & Form-1C.
                                    </p>
                                </div>
                            @elseif($isArchivedDev)
                                <div class="p-3 bg-slate-100 border border-slate-200 rounded-lg text-xs text-slate-700">
                                    <p class="font-bold text-slate-900 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Perkara Sudah Selesai / Diarsipkan
                                    </p>
                                    <p class="text-slate-600 mt-0.5 text-[11px]">Semua reminder perkara ini telah dinonaktifkan.
                                        Gunakan menu <strong>Reset State Simulasi</strong> di bawah jika ingin menguji alur perkara
                                        ini kembali.</p>
                                </div>
                            @endif

                            @if(!$isArchivedDev && $devActiveReminder)
                                <!-- Quick Action Buttons for Active Case -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <!-- Mode: H-3 -->
                                    <form action="{{ route('perkara.devSimulate', $perkara->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="mode" value="set_h3">
                                        <button type="submit"
                                            class="w-full flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-amber-500 hover:bg-amber-600 text-white shadow-xs transition-colors"
                                            title="Ubah deadline menjadi 3 hari lagi">
                                            <span class="w-2 h-2 rounded-full bg-amber-200"></span>
                                            Set H-3 (Peringatan)
                                        </button>
                                    </form>

                                    <!-- Mode: H-0 (Jatuh Tempo) -->
                                    <form action="{{ route('perkara.devSimulate', $perkara->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="mode" value="set_h0">
                                        <button type="submit"
                                            class="w-full flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-rose-500 hover:bg-rose-600 text-white shadow-xs transition-colors"
                                            title="Ubah deadline menjadi hari ini">
                                            <span class="w-2 h-2 rounded-full bg-rose-200"></span>
                                            Set H-0 (Jatuh Tempo)
                                        </button>
                                    </form>

                                    <!-- Mode: Overdue & Auto-Transition -->
                                    <form action="{{ route('perkara.devSimulate', $perkara->id) }}" method="POST"
                                        class="sm:col-span-2">
                                        @csrf
                                        <input type="hidden" name="mode" value="set_overdue">
                                        <button type="submit"
                                            class="w-full flex items-center justify-center gap-2 px-3 py-2.5 rounded-lg text-xs font-bold bg-gradient-to-r from-red-600 to-rose-700 hover:from-red-700 hover:to-rose-800 text-white shadow-sm transition-all"
                                            title="Ubah deadline menjadi kemarin dan eksekusi SLA Engine">
                                            <svg class="w-4 h-4 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                                stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                            </svg>
                                            @if($perkara->tahap_saat_ini === \App\Services\PerkaraWorkflowService::TAHAP_P19)
                                                <span>Simulasikan >14 Hari ➔ Auto-P20 & Arsipkan</span>
                                            @elseif($perkara->tahap_saat_ini === \App\Services\PerkaraWorkflowService::TAHAP_P21)
                                                <span>Simulasikan >14 Hari ➔ Auto-FORM-7 & Arsipkan</span>
                                            @elseif($perkara->tahap_saat_ini === \App\Services\PerkaraWorkflowService::TAHAP_MONITORING)
                                                <span>Simulasikan >30 Hari ➔ Eksekusi Alur Otomatis</span>
                                            @else
                                                <span>Set Overdue & Eksekusi Alur Otomatis</span>
                                            @endif
                                        </button>
                                    </form>
                                </div>

                                <!-- Custom Days Input & Trigger Engine -->
                                <div class="pt-3 border-t border-amber-200/80">
                                    <form action="{{ route('perkara.devSimulate', $perkara->id) }}" method="POST"
                                        class="flex items-center gap-2">
                                        @csrf
                                        <input type="hidden" name="mode" value="custom_days">
                                        <div class="relative flex-1">
                                            <input type="number" name="custom_days" placeholder="Misal: 1" value="1" min="-60"
                                                max="60" required
                                                class="w-full text-xs rounded-lg border border-amber-300 bg-white py-1.5 px-2.5 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                                            <span class="absolute right-2 top-1.5 text-[10px] text-gray-400">hari lagi</span>
                                        </div>
                                        <button type="submit"
                                            class="px-3 py-1.5 rounded-lg text-xs font-medium bg-amber-800 hover:bg-amber-900 text-white shrink-0 transition-colors">
                                            Terapkan Hari
                                        </button>
                                    </form>
                                </div>
                            @endif

                            <!-- Force Trigger Engine Button -->
                            <div class="pt-1">
                                <form action="{{ route('perkara.devSimulate', $perkara->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="mode" value="trigger_engine">
                                    <button type="submit"
                                        class="w-full flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-red-50 hover:bg-red-100 text-red-800 border border-red-200 transition-colors">
                                        <svg class="w-3.5 h-3.5 text-red-700" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                                        </svg>
                                        <span>Paksa Evaluasi SLA Engine Sekarang</span>
                                    </button>
                                </form>
                            </div>

                            <!-- Sandbox State Switcher / Reset Alur (Dev Only) -->
                            <div class="pt-3 border-t border-amber-200/80">
                                <p class="text-[11px] font-bold text-amber-950 mb-2 flex items-center gap-1">
                                    <span>⚡ Sandbox State Switcher (Uji Coba Ulang):</span>
                                </p>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-1.5">
                                    <form action="{{ route('perkara.devSimulate', $perkara->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="mode" value="reset_tahap">
                                        <input type="hidden" name="target_tahap" value="P-19">
                                        <button type="submit"
                                            class="w-full px-2 py-1.5 rounded text-[11px] font-semibold bg-white hover:bg-orange-50 text-orange-800 border border-orange-300 shadow-2xs transition-colors"
                                            title="Set perkara ke Tahap P-19 untuk menguji SLA 14 Hari & P-20">
                                            Set Tahap P-19 (14h)
                                        </button>
                                    </form>

                                    <form action="{{ route('perkara.devSimulate', $perkara->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="mode" value="reset_tahap">
                                        <input type="hidden" name="target_tahap" value="P-21">
                                        <button type="submit"
                                            class="w-full px-2 py-1.5 rounded text-[11px] font-semibold bg-white hover:bg-emerald-50 text-emerald-800 border border-emerald-300 shadow-2xs transition-colors"
                                            title="Set perkara ke Tahap P-21 untuk menguji SLA 14 Hari & FORM-7">
                                            Set Tahap P-21 (14h)
                                        </button>
                                    </form>

                                    <form action="{{ route('perkara.devSimulate', $perkara->id) }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="mode" value="reset_tahap">
                                        <input type="hidden" name="target_tahap" value="Monitoring">
                                        <button type="submit"
                                            class="w-full px-2 py-1.5 rounded text-[11px] font-semibold bg-white hover:bg-amber-50 text-amber-800 border border-amber-300 shadow-2xs transition-colors"
                                            title="Set perkara ke Monitoring Penyidikan untuk menguji siklus 30 Hari">
                                            Set Monitoring (30h)
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </details>
                @endif

            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="relative z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900/60 transition-opacity" id="deleteModalBackdrop"></div>
        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div
                    class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200">
                    <div class="p-6">
                        <div class="sm:flex sm:items-start">
                            <div
                                class="mx-auto flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-rose-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-rose-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                    stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                <h3 class="text-base font-semibold leading-6 text-slate-900" id="modal-title">Hapus Perkara
                                </h3>
                                <div class="mt-2">
                                    <p class="text-sm text-slate-600">Apakah Anda yakin ingin menghapus perkara ini beserta
                                        seluruh riwayat timeline dan remindernya? Tindakan ini tidak dapat dibatalkan.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div
                        class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                        <button type="button" onclick="closeDeleteModal()"
                            class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 transition-colors">Batal</button>
                        <form action="{{ route('perkara.destroy', $perkara->id) }}" method="POST" id="deleteForm">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex w-full justify-center rounded-lg bg-rose-600 hover:bg-rose-700 px-4 py-2 text-sm font-semibold text-white shadow-2xs transition-colors">Hapus
                                Perkara</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(isset($lastUndoableAction) && $lastUndoableAction && auth()->user()->isPidum())
        <!-- Undo Last Action Modal (Koreksi Administrasi) -->
        <div id="undoModal" class="relative z-50 hidden" aria-labelledby="undo-modal-title" role="dialog" aria-modal="true">
            <div class="fixed inset-0 bg-slate-900/60 transition-opacity" id="undoModalBackdrop" onclick="closeUndoModal()">
            </div>
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                    <div
                        class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-amber-300">
                        <form action="{{ route('perkara.undoPerkembangan', $perkara->id) }}" method="POST">
                            @csrf
                            <div
                                class="bg-gradient-to-r from-amber-50 via-white to-amber-50/40 px-6 py-4 border-b border-amber-200 flex items-center justify-between">
                                <div class="flex items-center gap-2.5">
                                    <div
                                        class="w-9 h-9 rounded-full bg-amber-100 text-amber-800 flex items-center justify-center font-bold">
                                        <svg class="w-5 h-5 text-amber-800" fill="none" viewBox="0 0 24 24"
                                            stroke="currentColor" stroke-width="2.2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-bold text-gray-900" id="undo-modal-title">Batalkan Langkah
                                            Terakhir</h3>
                                        <p class="text-xs text-amber-900/70">Koreksi administrasi alur perkara dengan audit
                                            trail resmi</p>
                                    </div>
                                </div>
                                <button type="button" onclick="closeUndoModal()" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="p-6 space-y-4">
                                <div
                                    class="bg-amber-50/80 border border-amber-200 rounded-lg p-3.5 text-xs text-amber-950 space-y-2">
                                    <div class="flex justify-between items-start gap-2">
                                        <span class="text-gray-500 shrink-0">Aksi yang dibatalkan:</span>
                                        <span
                                            class="font-bold text-gray-900 text-right">{{ $lastUndoableAction->status_kegiatan }}</span>
                                    </div>
                                    <div class="flex justify-between items-start gap-2 border-t border-amber-200/60 pt-1.5">
                                        <span class="text-gray-500 shrink-0">Waktu dicatat:</span>
                                        <span
                                            class="font-medium text-gray-800 text-right">{{ $lastUndoableAction->tanggal_kejadian->translatedFormat('d M Y') }}
                                            ({{ $lastUndoableAction->created_at->format('H:i') }} WIB)</span>
                                    </div>
                                    <div class="flex justify-between items-start gap-2 border-t border-amber-200/60 pt-1.5">
                                        <span class="text-gray-500 shrink-0">Status dipulihkan ke:</span>
                                        <span class="font-bold text-rose-900 text-right">
                                            {{ $lastUndoableAction->tahap_sebelumnya ?? 'Tahap Sebelumnya' }}
                                            @if($lastUndoableAction->status_sebelumnya)
                                                - {{ $lastUndoableAction->status_sebelumnya }}
                                            @endif
                                        </span>
                                    </div>
                                </div>

                                <div>
                                    <label for="alasan_pembatalan"
                                        class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1.5">
                                        Alasan Pembatalan / Koreksi <span class="text-red-500">*</span>
                                    </label>
                                    <textarea id="alasan_pembatalan" name="alasan_pembatalan" rows="3" required minlength="5"
                                        maxlength="500"
                                        placeholder="Contoh: Salah pilih opsi pada menu dropdown, seharusnya berkas belum lengkap P-19."
                                        class="w-full text-sm rounded-lg border border-gray-300 p-3 focus:outline-none focus:border-amber-600 focus:ring-2 focus:ring-amber-600/20"></textarea>
                                    <p class="text-[11px] text-gray-400 mt-1">Alasan pembatalan akan disimpan secara permanen
                                        pada log audit trail kedinasan.</p>
                                </div>
                            </div>

                            <div class="bg-gray-50 px-6 py-3.5 border-t border-gray-200 flex items-center justify-end gap-2.5">
                                <button type="button" onclick="closeUndoModal()"
                                    class="px-4 py-2 rounded-lg text-xs font-semibold text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 shadow-2xs">
                                    Batal
                                </button>
                                <button type="submit"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-bold text-white bg-amber-700 hover:bg-amber-800 shadow-sm transition">
                                    <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        stroke-width="2.2">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                                    </svg>
                                    Konfirmasi Batalkan Aksi
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

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

        function openUndoModal() {
            const undoModal = document.getElementById('undoModal');
            if (undoModal) {
                undoModal.classList.remove('hidden');
            }
        }

        function closeUndoModal() {
            const undoModal = document.getElementById('undoModal');
            if (undoModal) {
                undoModal.classList.add('hidden');
            }
        }
    </script>
@endsection