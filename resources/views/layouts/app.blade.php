<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIPETA') - Sistem Pengingat Tahapan Perkara</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|instrument-sans:400,500,600,700" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            if (!sidebar || !backdrop) return;

            const isClosed = sidebar.classList.contains('-translate-x-full');
            if (isClosed) {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
        }

        // Auto close backdrop if window resized to desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) {
                const backdrop = document.getElementById('sidebar-backdrop');
                if (backdrop && !backdrop.classList.contains('hidden')) {
                    backdrop.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }
            }
        });

        // Close mobile drawer on Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const sidebar = document.getElementById('sidebar');
                if (sidebar && !sidebar.classList.contains('-translate-x-full') && window.innerWidth < 1024) {
                    toggleSidebar();
                }
            }
        });

        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);

            // Toggle eye icon SVGs if present
            const eyeOpen = btn.querySelector('.eye-open');
            const eyeClosed = btn.querySelector('.eye-closed');
            if (eyeOpen && eyeClosed) {
                if (type === 'text') {
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

<body class="h-screen w-screen overflow-hidden bg-gray-50 text-gray-900 font-sans antialiased flex">

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="sidebar-backdrop" onclick="toggleSidebar()"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs z-30 lg:hidden hidden transition-opacity duration-300"
        aria-hidden="true">
    </div>

    <!-- Sidebar Navigation -->
    <aside id="sidebar"
        class="fixed lg:static inset-y-0 left-0 z-40 w-64 bg-white border-r border-gray-200 -translate-x-full lg:translate-x-0 transition-transform duration-200 ease-in-out flex flex-col h-screen shrink-0 shadow-xl lg:shadow-none">

        <!-- Sidebar Brand Header -->
        <div class="flex items-center justify-between px-5 h-16 border-b border-gray-200 bg-white shrink-0">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                <div
                    class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#700d1a] via-[#881337] to-[#3a060d] flex items-center justify-center shadow-md shadow-red-950/20 text-white shrink-0 group-hover:scale-105 transition-transform duration-200 border border-amber-400/30">
                    <svg class="w-5 h-5 text-amber-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/>
                        <path d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/>
                        <path d="M7 21h10"/>
                        <path d="M12 3v18"/>
                        <path d="M3 7h18"/>
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5">
                        <span class="text-base font-extrabold text-gray-900 tracking-tight leading-none">SIPETA</span>
                        <span
                            class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-red-50 text-red-700 border border-red-200 uppercase tracking-wider">PIDUM</span>
                    </div>
                    <p class="text-[11px] text-gray-500 font-medium truncate mt-0.5">Kejaksaan Negeri RI</p>
                </div>
            </a>

            <!-- Close button for mobile -->
            <button type="button" onclick="toggleSidebar()"
                class="lg:hidden p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"
                aria-label="Tutup navigasi">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Scrollable Navigation Items -->
        <nav class="flex-1 overflow-y-auto px-3.5 py-4 space-y-6 sidebar-scroll">

            <!-- Group: Menu Utama -->
            <div>
                <p class="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Menu Utama</p>
                <div class="space-y-1">
                    <!-- Dashboard -->
                    <a href="{{ route('dashboard') }}"
                        class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm transition-all duration-150 {{ request()->routeIs('dashboard') ? 'bg-red-50 text-red-800 font-bold shadow-2xs ring-1 ring-red-200/80 border-l-4 border-red-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 font-medium' }}">
                        <div class="flex items-center gap-3 min-w-0">
                            <svg class="w-5 h-5 {{ request()->routeIs('dashboard') ? 'text-red-700' : 'text-slate-400' }} shrink-0"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                            </svg>
                            <span class="truncate">Dashboard</span>
                        </div>
                        @if(request()->routeIs('dashboard'))
                            <span class="w-1.5 h-1.5 rounded-full bg-red-700 shrink-0"></span>
                        @endif
                    </a>

                    <!-- Data Perkara -->
                    <a href="{{ route('perkara.index') }}"
                        class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm transition-all duration-150 {{ request()->routeIs('perkara.*') ? 'bg-red-50 text-red-800 font-bold shadow-2xs ring-1 ring-red-200/80 border-l-4 border-red-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 font-medium' }}">
                        <div class="flex items-center gap-3 min-w-0">
                            <svg class="w-5 h-5 {{ request()->routeIs('perkara.*') ? 'text-red-700' : 'text-slate-400' }} shrink-0"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                            </svg>
                            <span class="truncate">Data Perkara</span>
                        </div>
                        @if(request()->routeIs('perkara.*'))
                            <span class="w-1.5 h-1.5 rounded-full bg-red-700 shrink-0"></span>
                        @endif
                    </a>

                    <!-- Jadwal Reminder -->
                    <a href="{{ route('reminders.index') }}"
                        class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm transition-all duration-150 {{ request()->routeIs('reminders.*') ? 'bg-red-50 text-red-800 font-bold shadow-2xs ring-1 ring-red-200/80 border-l-4 border-red-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 font-medium' }}">
                        <div class="flex items-center gap-3 min-w-0">
                            <svg class="w-5 h-5 {{ request()->routeIs('reminders.*') ? 'text-red-700' : 'text-slate-400' }} shrink-0"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                            </svg>
                            <span class="truncate">Jadwal Reminder</span>
                        </div>
                        @if(request()->routeIs('reminders.*'))
                            <span class="w-1.5 h-1.5 rounded-full bg-red-700 shrink-0"></span>
                        @endif
                    </a>
                </div>
            </div>

            <!-- Group: Audit & Log -->
            <div>
                <p class="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Audit & Log</p>
                <div class="space-y-1">
                    <!-- Timeline & SLA -->
                    <a href="{{ route('timelines.index') }}"
                        class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm transition-all duration-150 {{ request()->routeIs('timelines.*') ? 'bg-red-50 text-red-800 font-bold shadow-2xs ring-1 ring-red-200/80 border-l-4 border-red-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 font-medium' }}">
                        <div class="flex items-center gap-3 min-w-0">
                            <svg class="w-5 h-5 {{ request()->routeIs('timelines.*') ? 'text-red-700' : 'text-slate-400' }} shrink-0"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="truncate">Kronologis (Timeline)</span>
                        </div>
                        @if(request()->routeIs('timelines.*'))
                            <span class="w-1.5 h-1.5 rounded-full bg-red-700 shrink-0"></span>
                        @endif
                    </a>
                </div>
            </div>

            <!-- Group: Administrasi (Pidum Only) -->
            @if (auth()->user()->isPidum())
                <div>
                    <p class="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Administrasi</p>
                    <div class="space-y-1">
                        <a href="{{ route('users.index') }}"
                            class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm transition-all duration-150 {{ request()->routeIs('users.*') ? 'bg-red-50 text-red-800 font-bold shadow-2xs ring-1 ring-red-200/80 border-l-4 border-red-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 font-medium' }}">
                            <div class="flex items-center gap-3 min-w-0">
                                <svg class="w-5 h-5 {{ request()->routeIs('users.*') ? 'text-red-700' : 'text-slate-400' }} shrink-0"
                                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.243m15.016 0a9.145 9.145 0 01-2.198.445 6.66 6.66 0 00-1.026-.118 4.125 4.125 0 00-7.533 2.243m15.016 0a3.513 3.513 0 00-1.026-.118 4.125 4.125 0 00-7.533 2.243M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                                </svg>
                                <span class="truncate">Manajemen Pengguna</span>
                            </div>
                            @if(request()->routeIs('users.*'))
                                <span class="w-1.5 h-1.5 rounded-full bg-red-700 shrink-0"></span>
                            @endif
                        </a>
                    </div>
                </div>
            @endif

            <!-- Group: Pengaturan Akun -->
            <div>
                <p class="px-3 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400">Pengaturan</p>
                <div class="space-y-1">
                    <a href="{{ route('profile.edit') }}"
                        class="flex items-center justify-between px-3 py-2.5 rounded-xl text-sm transition-all duration-150 {{ request()->routeIs('profile.*') ? 'bg-red-50 text-red-800 font-bold shadow-2xs ring-1 ring-red-200/80 border-l-4 border-red-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100/80 font-medium' }}">
                        <div class="flex items-center gap-3 min-w-0">
                            <svg class="w-5 h-5 {{ request()->routeIs('profile.*') ? 'text-red-700' : 'text-slate-400' }} shrink-0"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="truncate">Pengaturan Profil</span>
                        </div>
                        @if(request()->routeIs('profile.*'))
                            <span class="w-1.5 h-1.5 rounded-full bg-red-700 shrink-0"></span>
                        @endif
                    </a>
                </div>
            </div>

        </nav>

        <!-- Sidebar Footer: Profile Card & Logout -->
        <div class="border-t border-gray-200 p-3 bg-slate-50/70 shrink-0">
            <!-- Compact User Identity Box -->
            <div class="p-2.5 rounded-xl bg-white border border-gray-200/80 shadow-xs mb-2.5 flex items-center gap-3">
                @if(auth()->user()->foto_profil)
                    <img src="{{ Storage::url(auth()->user()->foto_profil) }}" alt="{{ auth()->user()->name }}"
                        class="w-9 h-9 rounded-full object-cover border border-gray-200 shrink-0">
                @else
                    <div
                        class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#881337] via-[#991b1b] to-[#700d1a] text-white flex items-center justify-center text-xs font-bold uppercase shrink-0 shadow-xs border border-amber-400/20">
                        {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                    </div>
                @endif
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold text-gray-900 truncate leading-tight">{{ auth()->user()->name }}</p>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span
                            class="inline-block w-1.5 h-1.5 rounded-full {{ auth()->user()->isPidum() ? 'bg-red-600' : 'bg-amber-500' }}"></span>
                        <p class="text-[10px] text-gray-500 capitalize truncate font-medium">
                            {{ auth()->user()->role === 'pidum' ? 'Admin Pidum' : 'Jaksa Peneliti' }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Logout Button -->
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                    class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold text-red-700 hover:text-white hover:bg-red-700 border border-red-200 hover:border-red-700 transition-all duration-150 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                    </svg>
                    <span>Keluar dari Sistem</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- Main Application Column (Isolated Scroll Context) -->
    <div class="flex-1 flex flex-col h-screen min-w-0 overflow-hidden bg-gray-50">

        <!-- Stationary Top Navbar -->
        <header
            class="h-16 shrink-0 bg-white border-b border-gray-200 px-4 lg:px-6 flex items-center justify-between z-10">
            <div class="flex items-center gap-3 min-w-0">
                <!-- Hamburger Menu on Mobile -->
                <button type="button" onclick="toggleSidebar()"
                    class="lg:hidden p-2 -ml-2 rounded-lg text-gray-500 hover:text-gray-900 hover:bg-gray-100 transition-colors"
                    aria-label="Buka navigasi sidebar">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                <!-- Page Title -->
                <div class="flex items-center gap-2 truncate">
                    <h2 class="text-base font-bold text-gray-900 tracking-tight truncate">@yield('title', 'Dashboard')
                    </h2>
                </div>
            </div>

            <!-- Navbar Right Actions -->
            <div class="flex items-center gap-3 shrink-0">
                <!-- Role Badge -->
                <span
                    class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold {{ auth()->user()->isPidum() ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                    <span
                        class="w-1.5 h-1.5 rounded-full {{ auth()->user()->isPidum() ? 'bg-red-600' : 'bg-amber-500' }}"></span>
                    {{ auth()->user()->role === 'pidum' ? 'Admin Pidum' : 'Jaksa Peneliti' }}
                </span>

                <!-- User Profile Pill Link -->
                <a href="{{ route('profile.edit') }}"
                    class="flex items-center gap-2.5 p-1 pl-1.5 pr-2.5 rounded-full hover:bg-gray-100 transition-colors border border-transparent hover:border-gray-200 group"
                    title="Buka Pengaturan Profil">
                    @if(auth()->user()->foto_profil)
                        <img src="{{ Storage::url(auth()->user()->foto_profil) }}" alt="{{ auth()->user()->name }}"
                            class="w-8 h-8 rounded-full object-cover border border-gray-200">
                    @else
                        <div
                            class="w-8 h-8 rounded-full bg-gradient-to-br from-[#881337] to-[#700d1a] text-white flex items-center justify-center text-xs font-bold uppercase shadow-xs border border-amber-400/20">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                    @endif
                    <div class="hidden md:block text-left">
                        <p
                            class="text-xs font-semibold text-gray-900 leading-tight max-w-32.5 truncate group-hover:text-red-700 transition-colors">
                            {{ auth()->user()->name }}
                        </p>
                        <p class="text-[10px] text-gray-400 leading-tight truncate mt-0.5">
                            {{ auth()->user()->nip ?? auth()->user()->email }}
                        </p>
                    </div>
                </a>
            </div>
        </header>

        <!-- Dedicated Scrollable Content Container -->
        <main class="flex-1 overflow-y-auto p-4 lg:p-6 bg-gray-50/70 focus:outline-none">
            @yield('content')
        </main>
    </div>

    <!-- Fine scrollbar styling -->
    <style>
        .sidebar-scroll::-webkit-scrollbar {
            width: 4px;
        }

        .sidebar-scroll::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 4px;
        }

        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: #cbd5e1;
        }
    </style>
</body>

</html>