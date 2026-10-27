@extends('layouts.guest')

@section('title', 'Daftar Akun Baru - SIPETA Kejaksaan')

@section('content')
<div class="space-y-6">
    <!-- Header Formulir -->
    <div>
        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-red-50 text-red-700 border border-red-200/60 mb-3">
            <span class="w-1.5 h-1.5 rounded-full bg-red-600"></span>
            Registrasi Aparat Penegak Hukum & Staf
        </div>
        <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Buat Akun Kedinasan</h2>
        <p class="text-sm text-slate-500 mt-1.5">
            Pilih peran tugas Anda dan lengkapi data kedinasan untuk integrasi sistem perkara.
        </p>
    </div>

    <!-- Feedback Pesan Kesalahan / Error -->
    @if ($errors->any())
        <div class="p-4 rounded-xl bg-red-50/90 border border-red-200 text-sm text-red-800 shadow-sm flex items-start gap-3">
            <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <div class="flex-1">
                <p class="font-semibold text-red-900">Periksa Kembali Data Anda</p>
                <ul class="list-disc list-inside mt-1 space-y-0.5 text-xs text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- Form Registrasi -->
    <form method="POST" action="{{ route('register.post') }}" class="space-y-5">
        @csrf

        <!-- Pilihan Peran (Role Selector) dengan Kartu Interaktif -->
        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                Pilih Peran / Tugas Kedinasan <span class="text-red-500">*</span>
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                
                <!-- Opsi 1: Jaksa -->
                <label for="role-jaksa" class="relative block cursor-pointer select-none">
                    <input type="radio" name="role" value="jaksa" id="role-jaksa"
                        class="peer sr-only" {{ old('role', 'jaksa') === 'jaksa' ? 'checked' : '' }}>
                    <div class="p-3.5 rounded-xl border border-slate-200 bg-white hover:border-slate-300 peer-checked:border-red-600 peer-checked:bg-red-50/40 peer-checked:ring-2 peer-checked:ring-red-600/20 transition-all duration-200 text-center space-y-1.5 h-full flex flex-col justify-between items-center shadow-2xs">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 peer-checked:bg-red-100 flex items-center justify-center text-slate-600 peer-checked:text-red-700">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="m2 16 3-8 3 8c-.87.65-1.92 1-3 1s-2.13-.35-3-1Z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-800 peer-checked:text-red-900">Jaksa</p>
                            <p class="text-[10px] text-slate-400 peer-checked:text-red-700">Penuntut (JPU)</p>
                        </div>
                    </div>
                </label>

                <!-- Opsi 2: Pidum / Admin -->
                <label for="role-pidum" class="relative block cursor-pointer select-none">
                    <input type="radio" name="role" value="pidum" id="role-pidum"
                        class="peer sr-only" {{ old('role') === 'pidum' ? 'checked' : '' }}>
                    <div class="p-3.5 rounded-xl border border-slate-200 bg-white hover:border-slate-300 peer-checked:border-red-600 peer-checked:bg-red-50/40 peer-checked:ring-2 peer-checked:ring-red-600/20 transition-all duration-200 text-center space-y-1.5 h-full flex flex-col justify-between items-center shadow-2xs">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 peer-checked:bg-red-100 flex items-center justify-center text-slate-600 peer-checked:text-red-700">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-800 peer-checked:text-red-900">Admin PIDUM</p>
                            <p class="text-[10px] text-slate-400 peer-checked:text-red-700">Pengawas Alur</p>
                        </div>
                    </div>
                </label>

            </div>
            @error('role')
                <p class="text-xs text-red-600 font-medium mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <!-- Input Nama Lengkap -->
        <div>
            <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                Nama Lengkap & Gelar <span class="text-red-500">*</span>
            </label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-red-700 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                </div>
                <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                    class="w-full pl-11 pr-4 py-2.5 sm:py-3 bg-white border border-slate-300 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-red-600 focus:ring-4 focus:ring-red-600/10 shadow-xs transition duration-200 @error('name') border-red-500 @enderror"
                    placeholder="Nama lengkap sesuai NIP / KTA">
            </div>
            @error('name')
                <p class="text-xs text-red-600 font-medium mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <!-- Input Email Kedinasan -->
        <div>
            <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                Alamat Email Kedinasan <span class="text-red-500">*</span>
            </label>
            <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 group-focus-within:text-red-700 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>
                </div>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required
                    class="w-full pl-11 pr-4 py-2.5 sm:py-3 bg-white border border-slate-300 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-red-600 focus:ring-4 focus:ring-red-600/10 shadow-xs transition duration-200 @error('email') border-red-500 @enderror"
                    placeholder="nama@kejaksaan.go.id / polri.go.id">
            </div>
            @error('email')
                <p class="text-xs text-red-600 font-medium mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <!-- Input Kata Sandi & Konfirmasi (2 Kolom) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
            <div>
                <label for="password" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                    Kata Sandi <span class="text-red-500">*</span>
                </label>
                <div class="relative group">
                    <input id="password" type="password" name="password" required
                        class="w-full pl-3.5 pr-10 py-2.5 sm:py-3 bg-white border border-slate-300 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-red-600 focus:ring-4 focus:ring-red-600/10 shadow-xs transition duration-200 @error('password') border-red-500 @enderror"
                        placeholder="Min. 8 karakter">
                    <button type="button" onclick="togglePassword('password', this)"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition"
                        title="Tampilkan / Sembunyikan Kata Sandi">
                        <svg class="icon-eye-open w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        <svg class="icon-eye-closed w-4 h-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
                @error('password')
                    <p class="text-xs text-red-600 font-medium mt-1.5">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                    Ulangi Kata Sandi <span class="text-red-500">*</span>
                </label>
                <div class="relative group">
                    <input id="password_confirmation" type="password" name="password_confirmation" required
                        class="w-full pl-3.5 pr-10 py-2.5 sm:py-3 bg-white border border-slate-300 rounded-xl text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:border-red-600 focus:ring-4 focus:ring-red-600/10 shadow-xs transition duration-200"
                        placeholder="Ulangi sandi">
                    <button type="button" onclick="togglePassword('password_confirmation', this)"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 transition"
                        title="Tampilkan / Sembunyikan Kata Sandi">
                        <svg class="icon-eye-open w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        </svg>
                        <svg class="icon-eye-closed w-4 h-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <p class="text-[11px] text-slate-500">
            * Gunakan kombinasi minimal 8 karakter dengan huruf dan angka untuk keamanan data penanganan perkara.
        </p>

        <!-- Tombol Submit Registrasi -->
        <button type="submit"
            class="w-full py-3.5 px-6 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-[#881337] via-[#991b1b] to-[#7f1d1d] hover:from-[#700d1a] hover:via-[#881337] hover:to-[#5c0b15] shadow-lg shadow-red-950/20 hover:shadow-xl transition-all duration-200 transform hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2 cursor-pointer mt-2">
            <span>Daftar Akun SIPETA</span>
            <svg class="w-4 h-4 text-white/90" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
            </svg>
        </button>

        <!-- Link Navigasi Cepat ke Login -->
        <div class="pt-2 text-center">
            <p class="text-xs text-slate-500">
                Sudah memiliki akun terdaftar?
                <a href="{{ route('login') }}" class="font-bold text-red-700 hover:text-red-800 hover:underline transition">
                    Masuk ke akun di sini
                </a>
            </p>
        </div>
    </form>
</div>
@endsection