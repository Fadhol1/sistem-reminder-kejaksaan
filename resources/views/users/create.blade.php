@extends('layouts.app')

@section('title', 'Tambah Pengguna')

@section('content')
<div class="space-y-6 max-w-2xl mx-auto">
    <div class="flex items-center space-x-3">
        <a href="{{ route('users.index') }}" class="text-gray-500 hover:text-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="m15 18-6-6 6-6" />
            </svg>
        </a>
        <h3 class="text-xl font-semibold text-gray-900">Buat Akses Pengguna Baru</h3>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden p-6">
        <form action="{{ route('users.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600 @error('name') border-red-500 @enderror">
                @error('name')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Alamat Email</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600 @error('email') border-red-500 @enderror">
                <p class="mt-1 text-xs text-gray-500">Email ini akan digunakan untuk proses Login.</p>
                @error('email')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role / Peran</label>
                <select name="role" id="role" required class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600 @error('role') border-red-500 @enderror">
                    <option value="" disabled selected>-- Pilih Role Akses --</option>
                    <option value="jaksa" {{ old('role') == 'jaksa' ? 'selected' : '' }}>Jaksa</option>
                    <option value="penyidik" {{ old('role') == 'penyidik' ? 'selected' : '' }}>Penyidik</option>
                    <option value="pidum" {{ old('role') == 'pidum' ? 'selected' : '' }}>PIDUM (Admin Kejaksaan)</option>
                </select>
                @error('role')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>
            
            <div>
                <label for="nip" class="block text-sm font-medium text-gray-700 mb-1">NIP (Opsional)</label>
                <input type="text" name="nip" id="nip" value="{{ old('nip') }}" class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600 @error('nip') border-red-500 @enderror">
                @error('nip')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="nrp" class="block text-sm font-medium text-gray-700 mb-1">NRP (Opsional)</label>
                <input type="text" name="nrp" id="nrp" value="{{ old('nrp') }}" placeholder="Nomor Registrasi Penyidik" class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600 @error('nrp') border-red-500 @enderror">
                @error('nrp')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="nomor_hp" class="block text-sm font-medium text-gray-700 mb-1">Nomor HP (Opsional)</label>
                <input type="text" name="nomor_hp" id="nomor_hp" value="{{ old('nomor_hp') }}" placeholder="Contoh: 08123456789" class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600 @error('nomor_hp') border-red-500 @enderror">
                @error('nomor_hp')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="pangkat_golongan" class="block text-sm font-medium text-gray-700 mb-1">Pangkat / Golongan (Opsional)</label>
                <input type="text" name="pangkat_golongan" id="pangkat_golongan" value="{{ old('pangkat_golongan') }}" placeholder="Contoh: Jaksa Madya (IV/a)" class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600 @error('pangkat_golongan') border-red-500 @enderror">
                @error('pangkat_golongan')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="jabatan" class="block text-sm font-medium text-gray-700 mb-1">Jabatan (Opsional)</label>
                <input type="text" name="jabatan" id="jabatan" value="{{ old('jabatan') }}" class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600 @error('jabatan') border-red-500 @enderror">
                @error('jabatan')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="foto_profil" class="block text-sm font-medium text-gray-700 mb-1">Foto Profil (Opsional)</label>
                <input type="file" name="foto_profil" id="foto_profil" accept="image/jpeg,image/png,image/jpg" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-gray-300 rounded-md">
                <p class="mt-1 text-xs text-gray-500">Maksimal ukuran file 2MB (Hanya gambar JPG/PNG).</p>
                @error('foto_profil')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div class="pt-4 border-t border-gray-100">
                <h4 class="text-sm font-medium text-gray-900 mb-4">Pengaturan Kata Sandi</h4>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Kata Sandi Baru</label>
                        <div class="relative">
                            <input type="password" name="password" id="password" required class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 pr-10 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600 @error('password') border-red-500 @enderror">
                            <button type="button" onclick="togglePassword('password', this)" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                <svg class="h-5 w-5 eye-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path class="eye-open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path class="eye-open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    <path class="eye-closed hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Kata Sandi</label>
                        <div class="relative">
                            <input type="password" name="password_confirmation" id="password_confirmation" required class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 pr-10 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600">
                            <button type="button" onclick="togglePassword('password_confirmation', this)" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                <svg class="h-5 w-5 eye-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path class="eye-open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path class="eye-open" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    <path class="eye-closed hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <p class="mt-2 text-xs text-gray-500">Kata sandi minimal 8 karakter.</p>
            </div>

            <div class="flex justify-end pt-4 border-t border-gray-200 space-x-3 mt-6">
                <a href="{{ route('users.index') }}" class="inline-flex h-9 items-center justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600">
                    Batal
                </a>
                <button type="submit" class="inline-flex h-9 items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600">
                    Simpan Pengguna
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
