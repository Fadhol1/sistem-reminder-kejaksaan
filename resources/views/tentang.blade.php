@extends('layouts.app')

@section('title', 'Tentang Aplikasi & Tim Pengembang')

@section('content')
    <div class="space-y-8 max-w-6xl mx-auto pb-14">
        <!-- Header Page -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-gray-200 pb-6">
            <div>
                <div
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-red-50 text-[#881337] border border-red-200/80 mb-2.5 uppercase tracking-wider">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#881337]"></span>
                    Kolaborasi Akademik &amp; Kedinasan Penegakan Hukum
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-gray-900 tracking-tight">Tentang SIPETA &amp; Tim
                    Pengembang</h1>
                <p class="text-sm text-gray-500 mt-1">Sistem Pengingat Tahapan Berkas Perkara Pidana Umum Kejaksaan Negeri
                    Ogan Komering Ulu.</p>
            </div>
            <div class="flex items-center gap-2 self-start sm:self-auto">
                <span
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Production Live
                </span>
                <span
                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200 shadow-2xs">
                    v1.0.0
                </span>
            </div>
        </div>

        <!-- ========================================================
             SEKSI 1: TIM PENGEMBANG (3 DEVELOPERS)
             ======================================================== -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold text-gray-900 tracking-tight flex items-center gap-2">
                        <svg class="w-5 h-5 text-[#881337]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.243m15.016 0a9.145 9.145 0 01-2.198.445 6.66 6.66 0 00-1.026-.118 4.125 4.125 0 00-7.533 2.243m15.016 0a3.513 3.513 0 00-1.026-.118 4.125 4.125 0 00-7.533 2.243M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                        Tim Pengembang Sistem (Development Team)
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">Mahasiswa Program Studi Manajemen Informatika &bull; Kampus
                        Universitas Mahakarya Asia</p>
                </div>
                <span
                    class="hidden sm:inline-flex text-[11px] font-bold text-[#881337] bg-red-50 border border-red-200 px-2.5 py-1 rounded-full uppercase tracking-wider">
                    3 Pengembang
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">

                <!-- PENGEMBANG 1: Dheny Kurniawan -->
                <div
                    class="bg-white rounded-2xl border border-gray-200/90 shadow-xs hover:shadow-md transition-shadow overflow-hidden flex flex-col justify-between">
                    <div>
                        <!-- Header Cover Card -->
                        <div
                            class="h-20 bg-gradient-to-r from-[#700d1a] via-[#881337] to-[#5c0b15] relative p-3 flex items-start justify-between">
                            <div
                                class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:12px_12px]">
                            </div>
                            <span
                                class="relative z-10 text-[9px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded bg-black/40 text-amber-300 border border-amber-400/30 backdrop-blur-xs">
                                Lead Developer
                            </span>
                        </div>

                        <!-- Body Card -->
                        <div class="px-5 pb-4 pt-0 relative">
                            <!-- Avatar Inisial -->
                            <div class="-mt-9 mb-3">
                                <div
                                    class="w-16 h-16 rounded-xl bg-gradient-to-br from-amber-400 via-amber-500 to-amber-600 p-0.5 shadow-md shadow-black/20">
                                    <div
                                        class="w-full h-full bg-[#5c0b15] rounded-[10px] flex items-center justify-center text-white font-extrabold text-xl tracking-wider">
                                        DK
                                    </div>
                                </div>
                            </div>

                            <h3 class="text-base font-bold text-gray-900 leading-tight">Dheny Kurniawand</h3>
                            <p class="text-xs font-semibold text-[#881337] mt-0.5">Lead Software &amp; Backend Engineer</p>

                            <div class="mt-3 pt-3 border-t border-gray-100 space-y-1.5 text-xs text-gray-600">
                                <p class="text-gray-500 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 14l9-5-9-5-9 5 9 5z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                                    </svg>
                                    <span>Manajemen Informatika</span>
                                </p>
                                <p class="font-medium text-gray-800 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path
                                            d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a10.978 10.978 0 00-.25 2.378 8.002 8.002 0 007.414 7.973 1.002 1.002 0 00.586-.184 10.974 10.974 0 003.75-5.26 1.001 1.001 0 00-.75-1.42 1 1 0 00-.85.34 8.975 8.975 0 01-3.15 4.385A6.002 6.002 0 017 10.429c0-.77.108-1.516.31-2.227l2.69 1.153a1 1 0 00.788 0l7-3a1 1 0 000-1.84l-7-3z" />
                                    </svg>
                                    <span>Universitas Mahakarya Asia</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="px-5 py-3 bg-slate-50/70 border-t border-gray-100 text-[11px] text-gray-500">
                        Rekayasa SLA Engine, Mutex Concurrency, &amp; Cloudflare Tunnel
                    </div>
                </div>

                <!-- PENGEMBANG 2: Dendi Ananda Putra -->
                <div
                    class="bg-white rounded-2xl border border-gray-200/90 shadow-xs hover:shadow-md transition-shadow overflow-hidden flex flex-col justify-between">
                    <div>
                        <!-- Header Cover Card -->
                        <div
                            class="h-20 bg-gradient-to-r from-slate-800 via-slate-900 to-slate-950 relative p-3 flex items-start justify-between">
                            <div
                                class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:12px_12px]">
                            </div>
                            <span
                                class="relative z-10 text-[9px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded bg-black/40 text-blue-300 border border-blue-400/30 backdrop-blur-xs">
                                Co-Developer
                            </span>
                        </div>

                        <!-- Body Card -->
                        <div class="px-5 pb-4 pt-0 relative">
                            <!-- Avatar Inisial -->
                            <div class="-mt-9 mb-3">
                                <div
                                    class="w-16 h-16 rounded-xl bg-gradient-to-br from-blue-400 via-blue-500 to-indigo-600 p-0.5 shadow-md shadow-black/20">
                                    <div
                                        class="w-full h-full bg-slate-900 rounded-[10px] flex items-center justify-center text-white font-extrabold text-xl tracking-wider">
                                        DA
                                    </div>
                                </div>
                            </div>

                            <h3 class="text-base font-bold text-gray-900 leading-tight">Dendi Ananda Putra</h3>
                            <p class="text-xs font-semibold text-blue-700 mt-0.5">Fullstack &amp; Database Engineer</p>

                            <div class="mt-3 pt-3 border-t border-gray-100 space-y-1.5 text-xs text-gray-600">
                                <p class="text-gray-500 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 14l9-5-9-5-9 5 9 5z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                                    </svg>
                                    <span>Manajemen Informatika</span>
                                </p>
                                <p class="font-medium text-gray-800 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path
                                            d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a10.978 10.978 0 00-.25 2.378 8.002 8.002 0 007.414 7.973 1.002 1.002 0 00.586-.184 10.974 10.974 0 003.75-5.26 1.001 1.001 0 00-.75-1.42 1 1 0 00-.85.34 8.975 8.975 0 01-3.15 4.385A6.002 6.002 0 017 10.429c0-.77.108-1.516.31-2.227l2.69 1.153a1 1 0 00.788 0l7-3a1 1 0 000-1.84l-7-3z" />
                                    </svg>
                                    <span>Universitas Mahakarya Asia</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="px-5 py-3 bg-slate-50/70 border-t border-gray-100 text-[11px] text-gray-500">
                        Modul Perkara, Manajemen User, &amp; Relasi Database MariaDB
                    </div>
                </div>

                <!-- PENGEMBANG 3: Naufal Fadil Wibadi -->
                <div
                    class="bg-white rounded-2xl border border-gray-200/90 shadow-xs hover:shadow-md transition-shadow overflow-hidden flex flex-col justify-between">
                    <div>
                        <!-- Header Cover Card -->
                        <div
                            class="h-20 bg-gradient-to-r from-emerald-800 via-emerald-900 to-teal-950 relative p-3 flex items-start justify-between">
                            <div
                                class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:12px_12px]">
                            </div>
                            <span
                                class="relative z-10 text-[9px] font-extrabold uppercase tracking-wider px-2 py-0.5 rounded bg-black/40 text-emerald-300 border border-emerald-400/30 backdrop-blur-xs">
                                Co-Developer
                            </span>
                        </div>

                        <!-- Body Card -->
                        <div class="px-5 pb-4 pt-0 relative">
                            <!-- Avatar Inisial -->
                            <div class="-mt-9 mb-3">
                                <div
                                    class="w-16 h-16 rounded-xl bg-gradient-to-br from-emerald-400 via-teal-500 to-emerald-600 p-0.5 shadow-md shadow-black/20">
                                    <div
                                        class="w-full h-full bg-emerald-950 rounded-[10px] flex items-center justify-center text-white font-extrabold text-xl tracking-wider">
                                        NF
                                    </div>
                                </div>
                            </div>

                            <h3 class="text-base font-bold text-gray-900 leading-tight">Naufal Fadhil Wibadi</h3>
                            <p class="text-xs font-semibold text-emerald-700 mt-0.5">Frontend &amp; UI/UX Specialist</p>

                            <div class="mt-3 pt-3 border-t border-gray-100 space-y-1.5 text-xs text-gray-600">
                                <p class="text-gray-500 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24"
                                        stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 14l9-5-9-5-9 5 9 5z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                                    </svg>
                                    <span>Manajemen Informatika</span>
                                </p>
                                <p class="font-medium text-gray-800 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path
                                            d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a10.978 10.978 0 00-.25 2.378 8.002 8.002 0 007.414 7.973 1.002 1.002 0 00.586-.184 10.974 10.974 0 003.75-5.26 1.001 1.001 0 00-.75-1.42 1 1 0 00-.85.34 8.975 8.975 0 01-3.15 4.385A6.002 6.002 0 017 10.429c0-.77.108-1.516.31-2.227l2.69 1.153a1 1 0 00.788 0l7-3a1 1 0 000-1.84l-7-3z" />
                                    </svg>
                                    <span>Universitas Mahakarya Asia</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="px-5 py-3 bg-slate-50/70 border-t border-gray-100 text-[11px] text-gray-500">
                        Desain Antarmuka Tailwind CSS, Responsivitas Mobile, &amp; Modul Berita
                    </div>
                </div>

            </div>
        </div>

        <!-- ========================================================
             SEKSI 2: TENTANG SISTEM SIPETA & KEJAKSAAN NEGERI OKU
             ======================================================== -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 pt-2">

            <!-- Detail Aplikasi SIPETA -->
            <div class="lg:col-span-7 bg-white rounded-2xl border border-gray-200 shadow-xs p-6 sm:p-7 space-y-4">
                <div class="flex items-center gap-3.5 pb-4 border-b border-gray-100">
                    <div
                        class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#700d1a] to-[#881337] flex items-center justify-center text-white shrink-0 shadow-md">
                        <svg class="w-6 h-6 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z" />
                            <path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z" />
                            <path d="M7 21h10" />
                            <path d="M12 3v18" />
                            <path d="M3 7h18" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 leading-tight">Sistem Pengingat Tahapan Perkara
                            (SIPETA)</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Aplikasi Digitalisasi Pengawasan Prapenuntutan Berkas
                            Perkara Pidum</p>
                    </div>
                </div>

                <p class="text-xs sm:text-sm text-gray-600 leading-relaxed">
                    <strong>SIPETA</strong> dikembangkan sebagai wujud kontribusi karya inovasi teknologi mahasiswa
                    Universitas Mahakarya Asia untuk Seksi Tindak Pidana Umum (PIDUM) Kejaksaan Negeri Ogan Komering Ulu.
                    Sistem ini membantu aparat penegak hukum dan Jaksa Peneliti dalam memonitor batas waktu penanganan
                    berkas perkara secara tepat waktu, tertib administrasi, dan akuntabel sesuai dengan <strong>Kitab
                        Undang-Undang Hukum Acara Pidana (KUHAP)</strong>.
                </p>

                <div
                    class="bg-gradient-to-r from-red-50 to-amber-50/60 rounded-xl p-3.5 border border-red-200/70 flex items-center justify-between">
                    <span class="text-xs font-bold text-[#881337] uppercase tracking-wider">Doktrin Adhyaksa:</span>
                    <span class="text-xs font-extrabold text-amber-900 tracking-wide">
                        Satya &bull; Adhi &bull; Wicaksana
                    </span>
                </div>
            </div>

            <!-- Spesifikasi Teknologi & Server -->
            <div class="lg:col-span-5 bg-white rounded-2xl border border-gray-200 shadow-xs p-6 sm:p-7 space-y-4">
                <h3
                    class="text-sm font-bold text-gray-900 uppercase tracking-wider pb-3 border-b border-gray-100 flex items-center gap-2">
                    <svg class="w-4 h-4 text-[#881337]" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.75 5.1a3 3 0 012.4-1.1h7.7a3 3 0 012.4 1.1l2.15 3.45a4.5 4.5 0 01.9 2.7" />
                    </svg>
                    Arsitektur &amp; Lingkungan Produksi
                </h3>

                <div class="space-y-2.5 text-xs">
                    <div class="flex items-center justify-between py-1 border-b border-gray-100">
                        <span class="text-gray-500">Framework Backend</span>
                        <span class="font-bold text-gray-800">Laravel 12.x (PHP 8.2 FPM)</span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-gray-100">
                        <span class="text-gray-500">Database Engine</span>
                        <span class="font-bold text-gray-800">MariaDB 10.11 (InnoDB)</span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-gray-100">
                        <span class="text-gray-500">Container Runtime</span>
                        <span class="font-bold text-gray-800">Docker &amp; Compose v5</span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-gray-100">
                        <span class="text-gray-500">Reverse Proxy &amp; Web</span>
                        <span class="font-bold text-gray-800">Nginx 1.27 Alpine</span>
                    </div>
                    <div class="flex items-center justify-between py-1 border-b border-gray-100">
                        <span class="text-gray-500">Jaringan &amp; Enkripsi</span>
                        <span class="font-bold text-gray-800">Cloudflare Zero Trust (HTTPS)</span>
                    </div>
                    <div class="flex items-center justify-between py-1">
                        <span class="text-gray-500">Notifikasi Realtime</span>
                        <span class="font-bold text-gray-800">Firebase FCM v1 HTTP</span>
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer Copyright -->
        <div class="p-4 rounded-xl bg-slate-100/70 border border-slate-200 text-center text-xs text-gray-500">
            <p class="font-semibold text-gray-700">SIPETA (Sistem Pengingat Tahapan Berkas Perkara) &bull; Versi 1.0.0</p>
            <p class="text-[11px] text-gray-400 mt-1">
                Karya Mahasiswa Program Studi Manajemen Informatika Universitas Mahakarya Asia &bull; Kejaksaan Negeri Ogan
                Komering Ulu &copy; {{ date('Y') }}.
            </p>
        </div>
    </div>
@endsection