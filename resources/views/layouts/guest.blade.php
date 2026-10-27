<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIPETA - Sistem Pengingat Tahapan Perkara')</title>

    <!-- Google Fonts / Bunny Fonts for Plus Jakarta Sans & Instrument Sans -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|instrument-sans:400,500,600,700" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';

            const eyeOpen = btn.querySelector('.icon-eye-open');
            const eyeClosed = btn.querySelector('.icon-eye-closed');
            if (eyeOpen && eyeClosed) {
                if (isPassword) {
                    eyeOpen.classList.add('hidden');
                    eyeClosed.classList.remove('hidden');
                } else {
                    eyeOpen.classList.remove('hidden');
                    eyeClosed.classList.add('hidden');
                }
            }
        }
    </script>
</head>

<body class="h-full bg-slate-50 font-sans text-slate-800 antialiased selection:bg-red-500 selection:text-white">
    <div class="min-h-screen w-full lg:h-screen lg:overflow-hidden grid grid-cols-1 lg:grid-cols-12 bg-white">

        <!-- ========================================================
             KOLOM KIRI: SISI MERAH (IDENTITAS RESMI KEJAKSAAN & FITUR)
             ======================================================== -->
        <div class="lg:col-span-5 xl:col-span-5 2xl:col-span-5 relative bg-gradient-to-br from-[#700d1a] via-[#881337] to-[#3a060d] text-white flex flex-col justify-between p-6 sm:p-10 lg:p-10 xl:p-12 overflow-hidden shadow-2xl lg:shadow-none">
            
            <!-- Ornamen Dekoratif Latar Belakang (Guilloche / Mesh Halus) -->
            <div class="absolute inset-0 pointer-events-none opacity-[0.06] bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:24px_24px]"></div>
            <div class="absolute -top-32 -left-32 w-96 h-96 bg-red-500/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-32 -right-32 w-96 h-96 bg-amber-500/15 rounded-full blur-3xl pointer-events-none"></div>
            
            <!-- Watermark Timbangan Keadilan Raksasa Halus di Sudut -->
            <div class="absolute -right-16 -bottom-16 w-80 h-80 text-white/[0.04] pointer-events-none select-none">
                <svg viewBox="0 0 24 24" fill="currentColor" class="w-full h-full">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/>
                </svg>
            </div>

            <!-- BAGIAN ATAS: Header Identitas Resmi Kejaksaan RI -->
            <div class="relative z-10">
                <div class="flex items-center gap-3.5">
                    <!-- Lambang Adhyaksa / Shield Keadilan -->
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-400 via-amber-500 to-amber-600 p-[1.5px] shadow-lg shadow-amber-950/40 shrink-0 flex items-center justify-center">
                        <div class="w-full h-full bg-[#5c0b15] rounded-[10px] flex items-center justify-center">
                            <!-- SVG Timbangan Keadilan & Pedang Adhyaksa -->
                            <svg class="w-6 h-6 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/>
                                <path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/>
                                <path d="M7 21h10"/>
                                <path d="M12 3v18"/>
                                <path d="M3 7h18"/>
                            </svg>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-bold tracking-widest bg-amber-400/20 text-amber-300 border border-amber-400/30 uppercase">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                                Kejaksaan RI
                            </span>
                            <span class="text-xs text-rose-200/70 font-medium">Bidang PIDUM</span>
                        </div>
                        <h1 class="text-2xl font-extrabold tracking-tight text-white mt-0.5 flex items-center gap-2">
                            SIPETA
                            <span class="text-xs font-normal text-rose-200 px-2 py-0.5 rounded-full bg-white/10 border border-white/15">v2.4</span>
                        </h1>
                    </div>
                </div>

                <p class="text-xs sm:text-sm text-rose-100/80 mt-3 leading-relaxed max-w-md">
                    Sistem Pengingat Tahapan Perkara — Penegakan tenggat waktu SLA pidana umum secara transparan, terukur, dan akuntabel.
                </p>
            </div>

            <!-- BAGIAN TENGAH: Pilar Integritas & Standar Tata Kelola Hukum (Aman & Profesional untuk Publik) -->
            <div class="relative z-10 my-8 lg:my-6 xl:my-8 space-y-4">
                
                <!-- Card Pilar Tata Kelola Kedinasan -->
                <div class="bg-white/10 backdrop-blur-md border border-white/15 rounded-2xl p-5 shadow-2xl space-y-3.5">
                    <div class="flex items-center justify-between pb-3 border-b border-white/10">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                            <span class="text-xs font-bold text-white tracking-wider uppercase">Portal Kedinasan Resmi</span>
                        </div>
                        <span class="text-[10px] font-bold text-amber-300 bg-amber-400/20 border border-amber-300/30 px-2 py-0.5 rounded-full uppercase tracking-wider">
                            Akses Terbatas
                        </span>
                    </div>

                    <!-- 3 Pilar Tata Kelola -->
                    <div class="space-y-2.5 text-xs">
                        <div class="flex items-start gap-3 bg-black/20 rounded-xl p-3 border border-white/10">
                            <div class="w-7 h-7 rounded-lg bg-amber-400/20 text-amber-300 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-bold text-white">Kepastian Waktu & SLA</p>
                                <p class="text-rose-100/70 text-[11px] leading-relaxed mt-0.5">
                                    Menjamin penanganan tahapan berkas perkara pidana umum berjalan tertib sesuai batas waktu KUHAP & SOP Kejaksaan RI.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 bg-black/20 rounded-xl p-3 border border-white/10">
                            <div class="w-7 h-7 rounded-lg bg-emerald-400/20 text-emerald-300 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-7.5 7.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 21 4.5H3a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 3 19.5Z" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-bold text-white">Audit Trail Digital Permanen</p>
                                <p class="text-rose-100/70 text-[11px] leading-relaxed mt-0.5">
                                    Setiap riwayat perkembangan dicatat secara otomatis, transparan, dan tidak dapat diubah sepihak.
                                </p>
                            </div>
                        </div>

                        <div class="flex items-start gap-3 bg-black/20 rounded-xl p-3 border border-white/10">
                            <div class="w-7 h-7 rounded-lg bg-rose-400/20 text-rose-300 flex items-center justify-center shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                            </div>
                            <div>
                                <p class="font-bold text-white">Keamanan & Kerahasiaan Data</p>
                                <p class="text-rose-100/70 text-[11px] leading-relaxed mt-0.5">
                                    Akses terproteksi khusus bagi Penyidik Polri/PPNS, Jaksa Penuntut Umum, dan Pimpinan Kejaksaan.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tri Krama Adhyaksa Values Badge -->
                <div class="flex items-center justify-between px-4 py-2.5 bg-black/25 rounded-xl border border-white/10 text-xs text-rose-200">
                    <span class="font-bold text-amber-300 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-amber-400" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                        Tri Krama Adhyaksa:
                    </span>
                    <span class="font-medium text-[11px] tracking-wide">Satya • Adhi • Wicaksana</span>
                </div>
            </div>

            <!-- BAGIAN BAWAH: Kutipan Integritas & Identitas -->
            <div class="relative z-10 pt-4 border-t border-white/15 flex flex-col sm:flex-row lg:flex-col xl:flex-row items-start sm:items-center lg:items-start xl:items-center justify-between gap-2 text-xs text-rose-200/70">
                <p class="italic">"Keadilan yang tertunda adalah ketidakadilan."</p>
                <span class="text-[11px] text-amber-400/90 font-medium whitespace-nowrap">Korps Adhyaksa RI &copy; {{ date('Y') }}</span>
            </div>
        </div>

        <!-- ========================================================
             KOLOM KANAN: SISI PUTIH (FORMULIR AUTENTIKASI BERSIH & MEWAH)
             ======================================================== -->
        <div class="lg:col-span-7 xl:col-span-7 2xl:col-span-7 bg-white flex flex-col justify-between overflow-y-auto custom-auth-scroll min-h-[600px]">
            
            <!-- Header Navigasi / Switcher Tab (Masuk vs Daftar) -->
            <div class="px-6 sm:px-12 pt-6 sm:pt-8 flex items-center justify-between border-b border-slate-100 pb-4">
                <div class="flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-red-600"></span>
                    <span class="text-xs font-bold text-slate-700 tracking-wider uppercase">Portal Penegakan Hukum</span>
                </div>

                <!-- Tab Switcher Navigasi Pill -->
                <div class="inline-flex p-1 rounded-xl bg-slate-100 border border-slate-200/80 shadow-inner">
                    <a href="{{ route('login') }}" 
                        class="px-4 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200 {{ request()->routeIs('login') ? 'bg-white text-red-700 shadow-sm border border-slate-200/60 font-bold' : 'text-slate-500 hover:text-slate-800' }}">
                        Masuk
                    </a>
                    <a href="{{ route('register') }}" 
                        class="px-4 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200 {{ request()->routeIs('register') ? 'bg-white text-red-700 shadow-sm border border-slate-200/60 font-bold' : 'text-slate-500 hover:text-slate-800' }}">
                        Daftar Baru
                    </a>
                </div>
            </div>

            <!-- Konten Form Utama (Login / Register) -->
            <div class="flex-1 flex items-center justify-center py-8 sm:py-10 px-6 sm:px-12 lg:px-10 xl:px-16">
                <div class="w-full max-w-md">
                    @yield('content')
                </div>
            </div>

            <!-- Footer Bawah Hak Cipta & Keamanan -->
            <div class="px-6 sm:px-12 py-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-2 text-[11px] text-slate-400">
                <div class="flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-7.5 7.5h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 21 4.5H3a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 3 19.5Z" />
                    </svg>
                    <span>Sistem Terenkripsi & Terintegrasi Antar-Lembaga</span>
                </div>
                <span>Kejaksaan Agung Republik Indonesia</span>
            </div>

        </div>

    </div>
</body>

</html>