<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SIPETA') - Sistem Pengingat Tahapan Perkara</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('-translate-x-full');
        }
    </script>
</head>

<body class="bg-gray-50 text-[#1b1b18] min-h-screen">
    <div class="flex min-h-screen">
        <aside id="sidebar"
            class="fixed lg:static inset-y-0 left-0 z-40 w-64 bg-white border-r border-gray-200 -translate-x-full lg:translate-x-0 transition-transform duration-200 flex flex-col">
            <div class="flex items-center gap-3 px-5 h-16 border-b border-gray-200 shrink-0">
                <div class="w-9 h-9 rounded-lg bg-[#f53003] flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                        stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-base font-semibold text-gray-900 leading-none">SIPETA</h1>
                    <p class="text-[10px] text-gray-500 mt-0.5">Sistem Pengingat Tahapan Perkara</p>
                </div>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                <a href="{{ route('dashboard') }}"
                    class="nav-link {{ request()->routeIs('dashboard') ? 'nav-active' : '' }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.25 12l8.954-8.955a1.5 1.5 0 012.122 0L21.75 12M4.5 9.75v10.5a.75.75 0 00.75.75h4.5a.75.75 0 00.75-.75V15a.75.75 0 01.75-.75h3a.75.75 0 01.75.75v5.25a.75.75 0 00.75.75h4.5a.75.75 0 00.75-.75V9.75M8.25 3h7.5" />
                    </svg>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('perkara.index') }}" class="nav-link {{ request()->routeIs('perkara.*') ? 'nav-active' : '' }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    <span>Perkara</span>
                </a>

                <a href="#" class="nav-link">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                    </svg>
                    <span>Reminder</span>
                </a>

                <a href="#" class="nav-link">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Timeline</span>
                </a>

                @if (auth()->user()->isPidum())
                    <div class="pt-3 mt-3 border-t border-gray-200">
                        <p class="px-3 pb-2 text-[10px] font-semibold uppercase tracking-wider text-gray-400">Administrasi
                        </p>
                        <a href="#" class="nav-link">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.243m15.016 0a9.145 9.145 0 01-2.198.445 6.66 6.66 0 00-1.026-.118 4.125 4.125 0 00-7.533 2.243m15.016 0a3.513 3.513 0 00-1.026-.118 4.125 4.125 0 00-7.533 2.243M3 3l18 18M3 3l18 18" />
                            </svg>
                            <span>Pengguna</span>
                        </a>
                    </div>
                @endif
            </nav>

            <div class="border-t border-gray-200 p-3 shrink-0">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                        class="w-full flex items-center gap-3 px-3 py-2.5 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                        </svg>
                        <span>Keluar</span>
                    </button>
                </form>
            </div>
        </aside>

        <div id="sidebar-backdrop" onclick="toggleSidebar()" class="fixed inset-0 bg-black/30 z-30 lg:hidden hidden">
        </div>

        <div class="flex-1 flex flex-col min-w-0">
            <header
                class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-4 lg:px-6 shrink-0 sticky top-0 z-20">
                <div class="flex items-center gap-3">
                    <button onclick="toggleSidebar()" class="lg:hidden p-2 -ml-2 rounded-md hover:bg-gray-100">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                    <h2 class="text-base font-semibold text-gray-900">@yield('title', 'Dashboard')</h2>
                </div>

                <div class="flex items-center gap-3">
                    <span
                        class="hidden sm:inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-[#f53003]/10 text-[#f53003] capitalize">
                        {{ auth()->user()->role }}
                    </span>
                    <div class="flex items-center gap-2">
                        <div
                            class="w-8 h-8 rounded-full bg-gray-200 flex items-center justify-center text-xs font-semibold text-gray-600 uppercase">
                            {{ strtoupper(substr(auth()->user()->name, 0, 2)) }}
                        </div>
                        <span
                            class="hidden sm:inline text-sm font-medium text-gray-700">{{ auth()->user()->name }}</span>
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 lg:p-6 overflow-y-auto">
                @yield('content')
            </main>
        </div>
    </div>

    <style>
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.625rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #4b5563;
            transition: all 0.15s;
        }

        .nav-link:hover {
            background-color: #f9fafb;
            color: #111827;
        }

        .nav-active {
            background-color: #fef2f0;
            color: #f53003;
        }
    </style>
</body>

</html>