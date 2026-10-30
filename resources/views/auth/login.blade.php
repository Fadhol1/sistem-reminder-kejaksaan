@extends('layouts.guest')

@section('title', 'Masuk - SIPETA Kejaksaan')

@section('content')
<div class="space-y-6">
    <!-- Header Formulir -->
    <div>
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-red-50 text-red-700 border border-red-200/60 mb-3">
            <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>
            Autentikasi Pegawai & Aparat Penegak Hukum
        </div>
        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Selamat Datang Kembali</h2>
        <p class="text-sm text-slate-500 mt-1.5">
            Silakan masukkan kredensial akun kedinasan Anda untuk memantau tahapan perkara dan reminder SLA.
        </p>
    </div>

    <!-- Feedback Pesan Kesalahan / Error -->
    @if ($errors->any())
        <div class="p-4 rounded-xl bg-red-50/90 border border-red-200 text-sm text-red-800 shadow-sm flex items-start gap-3">
            <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <div class="flex-1">
                <p class="font-semibold text-red-900">Gagal Masuk</p>
                <ul class="list-disc list-inside mt-1 space-y-0.5 text-xs text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if (session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-sm text-emerald-800 shadow-sm flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <span class="text-xs font-medium">{{ session('status') }}</span>
        </div>
    @endif

    <!-- Form Login -->
    <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
        @csrf

        <!-- Input Email Kedinasan -->
        <div>
            <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                Email Kedinasan
            </label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-red-700 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                </div>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                    class="w-full pl-11 pr-4 py-3 bg-white border border-slate-300 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-red-600 focus:ring-4 focus:ring-red-600/10 shadow-xs transition duration-200 @error('email') border-red-500 @enderror"
                    placeholder="nama@kejaksaan.go.id / polri.go.id">
            </div>
            @error('email')
                <p class="text-xs text-red-600 font-medium mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <!-- Input Kata Sandi -->
        <div>
            <div class="flex items-center justify-between mb-2">
                <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                    Kata Sandi
                </label>
            </div>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-red-700 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </div>
                <input id="password" type="password" name="password" required
                    class="w-full pl-11 pr-11 py-3 bg-white border border-slate-300 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-red-600 focus:ring-4 focus:ring-red-600/10 shadow-xs transition duration-200 @error('password') border-red-500 @enderror"
                    placeholder="••••••••">
                <button type="button" onclick="togglePassword('password', this)"
                    class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition"
                    title="Tampilkan / Sembunyikan Kata Sandi">
                    <svg class="icon-eye-open w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <svg class="icon-eye-closed w-5 h-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                    </svg>
                </button>
            </div>
            @error('password')
                <p class="text-xs text-red-600 font-medium mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <!-- Opsi Ingat Saya -->
        <div class="flex items-center justify-between pt-1">
            <label class="flex items-center gap-2.5 text-xs text-slate-600 cursor-pointer select-none">
                <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-300 text-red-700 focus:ring-red-600 focus:ring-offset-0 transition cursor-pointer">
                <span>Ingat saya di perangkat ini</span>
            </label>
        </div>

        <!-- Tombol Submit Masuk -->
        <button type="submit"
            class="w-full py-3.5 px-6 rounded-xl text-sm font-bold text-white bg-[#881337] hover:bg-[#700d1a] shadow-sm hover:shadow transition-all duration-200 flex items-center justify-center gap-2 cursor-pointer">
            <span>Masuk ke Dashboard SIPETA</span>
            <svg class="w-4 h-4 text-white/90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
            </svg>
        </button>

        <!-- Keterangan Akses Tertutup Kedinasan -->
        <div class="pt-2 text-center">
            <p class="text-xs text-slate-500">
                Pendaftaran & pengelolaan akun kedinasan dilakukan oleh <span class="font-semibold text-slate-700">Administrator PIDUM</span>.
            </p>
        </div>
    </form>
</div>
@endsection