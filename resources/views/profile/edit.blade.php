@extends('layouts.app')

@section('title', 'Pengaturan Profil')

@section('content')
<div class="space-y-6 max-w-3xl mx-auto pb-10">
    <!-- Header Page -->
    <div class="flex items-center space-x-3">
        <a href="{{ route('dashboard') }}" class="p-2 rounded-lg text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" title="Kembali ke Dashboard">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6" />
            </svg>
        </a>
        <div>
            <h3 class="text-xl font-bold text-gray-900 tracking-tight">Pengaturan Profil Saya</h3>
            <p class="text-xs text-gray-500 mt-0.5">Kelola identitas kedinasan, kontak, dan kredensial akses akun Anda.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-sm text-emerald-800 shadow-2xs flex items-center gap-2.5" role="alert">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="bg-white rounded-xl border border-gray-200/80 shadow-xs overflow-hidden p-6 sm:p-7">
        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PATCH')

            <!-- Section 1: Foto Profil -->
            <div>
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4 pb-2 border-b border-gray-100">
                    1. Foto Profil
                </h4>
                <div class="flex items-center space-x-5">
                    <div class="shrink-0">
                        @if($user->foto_profil)
                            <img src="{{ Storage::url($user->foto_profil) }}" alt="Foto Profil" class="h-16 w-16 rounded-full object-cover border border-gray-200 shadow-2xs">
                        @else
                            <div class="h-16 w-16 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-400">
                                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="flex-1">
                        <label for="foto_profil" class="block text-sm font-medium text-gray-700 mb-1">Unggah Foto Baru (Opsional)</label>
                        <input type="file" name="foto_profil" id="foto_profil" accept="image/jpeg,image/png,image/jpg"
                            class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100 border border-gray-300 rounded-lg cursor-pointer">
                        <p class="mt-1 text-xs text-gray-500">Maksimal 2MB. Format: JPG, PNG.</p>
                        @error('foto_profil')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 2: Data Kontak & Akun -->
            <div>
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4 pb-2 border-b border-gray-100">
                    2. Data Kontak & Akun
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                            Nama Lengkap <span class="text-rose-600 font-bold">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                            class="flex h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 @error('name') border-red-500 @enderror">
                        @error('name')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                            Alamat Email <span class="text-rose-600 font-bold">*</span>
                        </label>
                        <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                            class="flex h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 @error('email') border-red-500 @enderror">
                        @error('email')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="nomor_hp" class="block text-sm font-medium text-gray-700 mb-1">Nomor HP / WhatsApp (Opsional)</label>
                        <input type="text" name="nomor_hp" id="nomor_hp" value="{{ old('nomor_hp', $user->nomor_hp) }}" placeholder="Contoh: 08123456789"
                            class="flex h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 @error('nomor_hp') border-red-500 @enderror">
                        @error('nomor_hp')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 3: Data Kepegawaian Kedinasan -->
            <div>
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4 pb-2 border-b border-gray-100">
                    3. Data Kedinasan (Opsional)
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="nip" class="block text-sm font-medium text-gray-700 mb-1">NIP</label>
                        <input type="text" name="nip" id="nip" value="{{ old('nip', $user->nip) }}" placeholder="Nomor Induk Pegawai"
                            class="flex h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 @error('nip') border-red-500 @enderror">
                        @error('nip')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="nrp" class="block text-sm font-medium text-gray-700 mb-1">NRP</label>
                        <input type="text" name="nrp" id="nrp" value="{{ old('nrp', $user->nrp) }}" placeholder="Nomor Registrasi Pegawai"
                            class="flex h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 @error('nrp') border-red-500 @enderror">
                        @error('nrp')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="pangkat_golongan" class="block text-sm font-medium text-gray-700 mb-1">Pangkat / Golongan</label>
                        <input type="text" name="pangkat_golongan" id="pangkat_golongan" value="{{ old('pangkat_golongan', $user->pangkat_golongan) }}"
                            class="flex h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 @error('pangkat_golongan') border-red-500 @enderror">
                        @error('pangkat_golongan')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="jabatan" class="block text-sm font-medium text-gray-700 mb-1">Jabatan</label>
                        <input type="text" name="jabatan" id="jabatan" value="{{ old('jabatan', $user->jabatan) }}"
                            class="flex h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 @error('jabatan') border-red-500 @enderror">
                        @error('jabatan')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 4: Ganti Kata Sandi -->
            <div class="pt-2">
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-1 pb-2 border-b border-gray-100">
                    4. Ganti Kata Sandi (Opsional)
                </h4>
                <p class="text-xs text-gray-500 mb-4">Kosongkan kolom di bawah jika Anda tidak ingin mengubah kata sandi.</p>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Kata Sandi Baru</label>
                        <div class="relative">
                            <input type="password" name="password" id="password" placeholder="Minimal 8 karakter"
                                class="flex h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 pr-10 text-sm focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20 @error('password') border-red-500 @enderror">
                            <button type="button" onclick="togglePassword('password', this)" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600" title="Tampilkan/Sembunyikan">
                                <svg class="h-4 w-4 eye-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path class="eye-open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path class="eye-open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    <path class="eye-closed hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Kata Sandi Baru</label>
                        <div class="relative">
                            <input type="password" name="password_confirmation" id="password_confirmation" placeholder="Ulangi kata sandi"
                                class="flex h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 pr-10 text-sm focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-600/20">
                            <button type="button" onclick="togglePassword('password_confirmation', this)" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600" title="Tampilkan/Sembunyikan">
                                <svg class="h-4 w-4 eye-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path class="eye-open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path class="eye-open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    <path class="eye-closed hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-end pt-5 border-t border-gray-200 space-x-3 mt-6">
                <a href="{{ route('dashboard') }}" class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-2xs hover:bg-gray-50 transition">
                    Batal
                </a>
                <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-[#881337] hover:bg-[#700d1a] px-5 py-2 text-sm font-semibold text-white shadow-sm transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
