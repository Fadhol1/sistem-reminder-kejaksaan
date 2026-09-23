<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Sistem Pengingat Tahapan Perkara'))</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-white text-[#1b1b18] min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 px-4">
    <div class="w-full sm:max-w-md">
        <div class="flex items-center justify-center mb-8">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-[#f53003] flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
                    </svg>
                </div>
                <div>
<h1 class="text-lg font-semibold text-gray-900 leading-none">SIPETA</h1>
                        <p class="text-xs text-gray-500 mt-0.5">Sistem Pengingat Tahapan Perkara</p>
                </div>
            </div>
        </div>

        <main class="bg-gray-50 shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] rounded-xl p-6 sm:p-8">
            @yield('content')
        </main>

        <footer class="mt-6 text-center text-xs text-gray-500">
            &copy; {{ date('Y') }} Kejaksaan Negeri. Sistem Pengingat Tahapan Perkara.
        </footer>
    </div>
</body>

</html>