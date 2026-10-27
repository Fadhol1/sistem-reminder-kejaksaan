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
            <button type="button" onclick="openDeleteModal()" class="inline-flex items-center justify-center rounded-lg text-sm font-medium focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600 bg-red-50 border border-red-200 text-red-700 shadow-2xs hover:bg-red-100 h-9 px-3.5 py-2 transition-colors">
                <svg class="mr-1.5 h-4 w-4 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                </svg>
                Hapus Perkara
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
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 flex items-center justify-between">
                    <h4 class="font-semibold text-gray-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
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
                                $tahapBg = match($timeline->tahap) {
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
                                    @if($isSystem)
                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-purple-600 text-white ring-4 ring-white shadow-2xs">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" />
                                            </svg>
                                        </span>
                                    @else
                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-900 text-white ring-4 ring-white shadow-2xs group-hover:bg-red-700 transition">
                                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                            </svg>
                                        </span>
                                    @endif
                                </div>

                                <div class="bg-gray-50/70 hover:bg-white rounded-lg p-3.5 border border-gray-200 transition">
                                    <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border {{ $tahapBg }}">
                                                {{ $timeline->tahap }}
                                            </span>
                                            @if($isSystem)
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-purple-100 text-purple-800 border border-purple-200">
                                                    🤖 Otomasi Sistem
                                                </span>
                                            @else
                                                <span class="text-xs text-gray-500 font-medium">
                                                    Oleh: <strong class="text-gray-700">{{ $timeline->user->name ?? 'Petugas' }}</strong>
                                                </span>
                                            @endif
                                        </div>
                                        <time class="text-xs text-gray-400 font-medium">
                                            {{ $timeline->tanggal_kejadian->translatedFormat('d M Y') }} • {{ $timeline->created_at->format('H:i') }} WIB
                                        </time>
                                    </div>

                                    <h5 class="text-sm font-bold text-gray-900">
                                        {{ $timeline->status_kegiatan }}
                                    </h5>

                                    @if($timeline->catatan)
                                        <div class="mt-2 text-xs text-gray-600 italic bg-white p-2.5 rounded border border-gray-100">
                                            "{{ $timeline->catatan }}"
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
                        <span class="inline-block rounded-md bg-red-50 px-2.5 py-1 text-sm font-semibold text-red-800 ring-1 ring-inset ring-red-700/20">{{ $perkara->tahap_saat_ini }}</span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Status</p>
                        <p class="font-semibold text-gray-900">{{ $perkara->status_saat_ini }}</p>
                    </div>

                    @if(str_contains($perkara->status_saat_ini, 'FORM-3'))
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700">
                            <p class="font-semibold text-slate-900 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Alur Monitoring Selesai (Arsip)
                            </p>
                            <p class="mt-1 text-slate-600">Berkas SPDP telah dikembalikan kepada Penyidik dan administrasi perkara disimpan ke arsip. Seluruh jadwal reminder dinonaktifkan.</p>
                        </div>
                    @elseif(str_contains($perkara->status_saat_ini, 'P-20'))
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700">
                            <p class="font-semibold text-slate-900 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                SPDP Dikembalikan (P-20 / Arsip)
                            </p>
                            <p class="mt-1 text-slate-600">Waktu penyidikan tambahan oleh Penyidik telah habis (>14 hari pasca P-19). Berkas SPDP dikembalikan kepada Penyidik dan perkara disimpan ke arsip. Seluruh jadwal reminder dinonaktifkan.</p>
                        </div>
                    @elseif(str_contains($perkara->status_saat_ini, 'FORM-7'))
                        <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700">
                            <p class="font-semibold text-slate-900 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Berkas & SPDP Dikembalikan (FORM-7 / Arsip)
                            </p>
                            <p class="mt-1 text-slate-600">Tenggat penyerahan tanggung jawab Tersangka dan Barang Bukti (Tahap II) terlewati (>14 hari pasca P-21). Berkas Perkara dan SPDP dikembalikan kepada Penyidik dan diarsipkan. Seluruh jadwal reminder dinonaktifkan.</p>
                        </div>
                    @elseif($perkara->status_saat_ini === 'Tahap II Selesai')
                        <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-xs text-emerald-800">
                            <p class="font-semibold text-emerald-900 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Perkara Selesai (Tahap II)
                            </p>
                            <p class="mt-1 text-emerald-700">Penyerahan Tersangka dan Barang Bukti telah selesai dilaksanakan secara lengkap.</p>
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

            @if(app()->environment('local') || config('app.debug'))
            @php
                $isArchivedDev = str_contains($perkara->status_saat_ini, 'FORM-3') ||
                                 str_contains($perkara->status_saat_ini, 'P-20') ||
                                 str_contains($perkara->status_saat_ini, 'FORM-7') ||
                                 $perkara->status_saat_ini === 'Tahap II Selesai';
                $devActiveReminder = $perkara->activeReminders->first();
            @endphp
            <!-- Dev / Sandbox SLA Simulator Card (Khusus Local Environment) -->
            <div class="bg-gradient-to-br from-amber-50/80 via-white to-orange-50/60 rounded-xl border-2 border-dashed border-amber-300 shadow-sm overflow-hidden">
                <div class="px-5 py-3.5 bg-amber-100/80 border-b border-amber-200 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-600 text-white tracking-wider uppercase">DEV TOOL</span>
                        <h4 class="font-bold text-xs sm:text-sm text-amber-950">Simulator Waktu & SLA Engine</h4>
                    </div>
                    <span class="text-[10px] font-semibold text-amber-800 bg-amber-200/90 px-2 py-0.5 rounded-full">Local Testing</span>
                </div>

                <div class="p-5 space-y-4">
                    <!-- Contextual SLA Target Info -->
                    @if($perkara->tahap_saat_ini === \App\Services\PerkaraWorkflowService::TAHAP_P19)
                        <div class="p-3 bg-orange-100/70 border border-orange-200 rounded-lg text-xs text-orange-950 space-y-1">
                            <div class="flex items-center gap-1.5 font-bold text-orange-900">
                                <span class="px-1.5 py-0.5 bg-orange-600 text-white rounded text-[10px] uppercase font-bold tracking-wider">Uji SLA 14 Hari P-19</span>
                                <span>Pemenuhan Petunjuk Jaksa</span>
                            </div>
                            <p class="text-orange-900/90 leading-relaxed text-[11px]">
                                Penyidik wajib memenuhi petunjuk jaksa maksimal 14 hari. Jika melewati batas waktu (>14 hari), <strong>SLA Engine otomatis menerbitkan P-20</strong> (Pengembalian SPDP), mengarsipkan perkara, dan menutup seluruh reminder.
                            </p>
                        </div>
                    @elseif($perkara->tahap_saat_ini === \App\Services\PerkaraWorkflowService::TAHAP_P21)
                        <div class="p-3 bg-emerald-100/70 border border-emerald-200 rounded-lg text-xs text-emerald-950 space-y-1">
                            <div class="flex items-center gap-1.5 font-bold text-emerald-900">
                                <span class="px-1.5 py-0.5 bg-emerald-600 text-white rounded text-[10px] uppercase font-bold tracking-wider">Uji SLA 14 Hari P-21</span>
                                <span>Penyerahan Tanggung Jawab Tahap II</span>
                            </div>
                            <p class="text-emerald-900/90 leading-relaxed text-[11px]">
                                Penyidik wajib menyerahkan Tersangka & Barang Bukti maksimal 14 hari pasca P-21. Jika melewati batas waktu (>14 hari), <strong>SLA Engine otomatis menerbitkan SOP FORM-7</strong> (Pengembalian Berkas & SPDP ke Penyidik), mengarsipkan perkara, dan menutup seluruh reminder.
                            </p>
                        </div>
                    @elseif($perkara->tahap_saat_ini === \App\Services\PerkaraWorkflowService::TAHAP_MONITORING)
                        <div class="p-3 bg-amber-100/70 border border-amber-200 rounded-lg text-xs text-amber-950 space-y-1">
                            <div class="flex items-center gap-1.5 font-bold text-amber-900">
                                <span class="px-1.5 py-0.5 bg-amber-600 text-white rounded text-[10px] uppercase font-bold tracking-wider">Uji SLA Siklus 30 Hari</span>
                                <span>Monitoring Penyidikan</span>
                            </div>
                            <p class="text-amber-900/90 leading-relaxed text-[11px]">
                                SPDP > 30 hari tanpa berkas ➔ <strong>P-17</strong> ➔ > 30 hari ➔ <strong>SOP FORM-2</strong> ➔ > 30 hari ➔ <strong>SOP FORM-3 (Arsip & Tutup Reminder)</strong>.
                            </p>
                        </div>
                    @elseif($perkara->tahap_saat_ini === \App\Services\PerkaraWorkflowService::TAHAP_KOORDINASI)
                        <div class="p-3 bg-blue-100/70 border border-blue-200 rounded-lg text-xs text-blue-950 space-y-1">
                            <div class="flex items-center gap-1.5 font-bold text-blue-900">
                                <span class="px-1.5 py-0.5 bg-blue-600 text-white rounded text-[10px] uppercase font-bold tracking-wider">Uji SLA 3 Hari</span>
                                <span>Koordinasi Awal</span>
                            </div>
                            <p class="text-blue-900/90 leading-relaxed text-[11px]">
                                Batas waktu koordinasi awal adalah 3 hari. Jika lewat, sistem mencatat tindak lanjut Laporan SOP Form-1B & Form-1C.
                            </p>
                        </div>
                    @elseif($isArchivedDev)
                        <div class="p-3 bg-slate-100 border border-slate-200 rounded-lg text-xs text-slate-700">
                            <p class="font-bold text-slate-900 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                Perkara Sudah Selesai / Diarsipkan
                            </p>
                            <p class="text-slate-600 mt-0.5 text-[11px]">Semua reminder perkara ini telah dinonaktifkan. Gunakan menu <strong>Reset State Simulasi</strong> di bawah jika ingin menguji alur perkara ini kembali.</p>
                        </div>
                    @endif

                    @if(!$isArchivedDev && $devActiveReminder)
                    <!-- Quick Action Buttons for Active Case -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <!-- Mode: H-3 -->
                        <form action="{{ route('perkara.devSimulate', $perkara->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="mode" value="set_h3">
                            <button type="submit" class="w-full flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-amber-500 hover:bg-amber-600 text-white shadow-xs transition-colors" title="Ubah deadline menjadi 3 hari lagi">
                                <span class="w-2 h-2 rounded-full bg-amber-200"></span>
                                Set H-3 (Peringatan)
                            </button>
                        </form>

                        <!-- Mode: H-0 (Jatuh Tempo) -->
                        <form action="{{ route('perkara.devSimulate', $perkara->id) }}" method="POST">
                            @csrf
                            <input type="hidden" name="mode" value="set_h0">
                            <button type="submit" class="w-full flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-rose-500 hover:bg-rose-600 text-white shadow-xs transition-colors" title="Ubah deadline menjadi hari ini">
                                <span class="w-2 h-2 rounded-full bg-rose-200"></span>
                                Set H-0 (Jatuh Tempo)
                            </button>
                        </form>

                        <!-- Mode: Overdue & Auto-Transition -->
                        <form action="{{ route('perkara.devSimulate', $perkara->id) }}" method="POST" class="sm:col-span-2">
                            @csrf
                            <input type="hidden" name="mode" value="set_overdue">
                            <button type="submit" class="w-full flex items-center justify-center gap-2 px-3 py-2.5 rounded-lg text-xs font-bold bg-gradient-to-r from-red-600 to-rose-700 hover:from-red-700 hover:to-rose-800 text-white shadow-sm transition-all" title="Ubah deadline menjadi kemarin dan eksekusi SLA Engine">
                                <svg class="w-4 h-4 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                                </svg>
                                @if($perkara->tahap_saat_ini === \App\Services\PerkaraWorkflowService::TAHAP_P19)
                                    <span>🔥 Simulasikan >14 Hari ➔ Auto-P20 & Arsipkan</span>
                                @elseif($perkara->tahap_saat_ini === \App\Services\PerkaraWorkflowService::TAHAP_P21)
                                    <span>🔥 Simulasikan >14 Hari ➔ Auto-FORM-7 & Arsipkan</span>
                                @elseif($perkara->tahap_saat_ini === \App\Services\PerkaraWorkflowService::TAHAP_MONITORING)
                                    <span>🔥 Simulasikan >30 Hari ➔ Eksekusi Alur Otomatis</span>
                                @else
                                    <span>🔥 Set Overdue & Eksekusi Alur Otomatis</span>
                                @endif
                            </button>
                        </form>
                    </div>

                    <!-- Custom Days Input & Trigger Engine -->
                    <div class="pt-3 border-t border-amber-200/80">
                        <form action="{{ route('perkara.devSimulate', $perkara->id) }}" method="POST" class="flex items-center gap-2">
                            @csrf
                            <input type="hidden" name="mode" value="custom_days">
                            <div class="relative flex-1">
                                <input type="number" name="custom_days" placeholder="Misal: 1" value="1" min="-60" max="60" required
                                    class="w-full text-xs rounded-lg border border-amber-300 bg-white py-1.5 px-2.5 focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                                <span class="absolute right-2 top-1.5 text-[10px] text-gray-400">hari lagi</span>
                            </div>
                            <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-medium bg-amber-800 hover:bg-amber-900 text-white shrink-0 transition-colors">
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
                            <button type="submit" class="w-full flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-red-50 hover:bg-red-100 text-red-800 border border-red-200 transition-colors">
                                <svg class="w-3.5 h-3.5 text-red-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
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
                                <button type="submit" class="w-full px-2 py-1.5 rounded text-[11px] font-semibold bg-white hover:bg-orange-50 text-orange-800 border border-orange-300 shadow-2xs transition-colors" title="Set perkara ke Tahap P-19 untuk menguji SLA 14 Hari & P-20">
                                    Set Tahap P-19 (14h)
                                </button>
                            </form>

                            <form action="{{ route('perkara.devSimulate', $perkara->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="mode" value="reset_tahap">
                                <input type="hidden" name="target_tahap" value="P-21">
                                <button type="submit" class="w-full px-2 py-1.5 rounded text-[11px] font-semibold bg-white hover:bg-emerald-50 text-emerald-800 border border-emerald-300 shadow-2xs transition-colors" title="Set perkara ke Tahap P-21 untuk menguji SLA 14 Hari & FORM-7">
                                    Set Tahap P-21 (14h)
                                </button>
                            </form>

                            <form action="{{ route('perkara.devSimulate', $perkara->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="mode" value="reset_tahap">
                                <input type="hidden" name="target_tahap" value="Monitoring">
                                <button type="submit" class="w-full px-2 py-1.5 rounded text-[11px] font-semibold bg-white hover:bg-amber-50 text-amber-800 border border-amber-300 shadow-2xs transition-colors" title="Set perkara ke Monitoring Penyidikan untuk menguji siklus 30 Hari">
                                    Set Monitoring (30h)
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            @endif

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
                                <select name="action_id" required class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus:border-red-600 focus-visible:ring-2 focus-visible:ring-red-600/20">
                                    <option value="" disabled selected>-- Pilih Aksi --</option>
                                    @foreach($availableActions as $action)
                                        <option value="{{ $action['id'] }}">{{ $action['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                                <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" required class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus:border-red-600 focus-visible:ring-2 focus-visible:ring-red-600/20">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan (Opsional)</label>
                                <textarea name="catatan" rows="2" class="flex w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus:border-red-600 focus-visible:ring-2 focus-visible:ring-red-600/20"></textarea>
                            </div>

                            <button type="submit" class="w-full inline-flex h-10 items-center justify-center rounded-lg bg-gradient-to-r from-[#881337] via-[#991b1b] to-[#7f1d1d] hover:from-[#700d1a] hover:via-[#881337] hover:to-[#5c0b15] px-4 py-2 text-sm font-semibold text-white shadow-sm transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-600">
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
