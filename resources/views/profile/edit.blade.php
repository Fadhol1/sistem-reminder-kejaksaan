@extends('layouts.app')

@section('title', 'Pengaturan Profil')

@section('content')
<div class="space-y-6 max-w-2xl mx-auto">
    <div class="flex items-center space-x-3">
        <h3 class="text-xl font-semibold text-gray-900">Pengaturan Profil Saya</h3>
    </div>

    @if(session('success'))
        <div class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200" role="alert">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden p-6">
        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PATCH')

            <div class="pb-4 border-b border-gray-100 flex items-center space-x-6">
                <div class="shrink-0">
                    @if($user->foto_profil)
                        <img src="{{ Storage::url($user->foto_profil) }}" alt="Foto Profil" class="h-24 w-24 rounded-full object-cover border border-gray-200 shadow-sm">
                    @else
                        <div class="h-24 w-24 rounded-full bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-400">
                            <svg class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                            </svg>
                        </div>
                    @endif
                </div>
                <div class="flex-1">
                    <label for="foto_profil" class="block text-sm font-medium text-gray-700 mb-2">Unggah Foto Baru (Opsional)</label>
                    <input type="file" name="foto_profil" id="foto_profil" accept="image/jpeg,image/png,image/jpg" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-gray-300 rounded-md">
                    <p class="mt-1 text-xs text-gray-500">Maks 2MB. Format: JPG, PNG.</p>
                    @error('foto_profil')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 border-b border-gray-100 pb-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Lengkap</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600 @error('name') border-red-500 @enderror">
                    @error('name')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Alamat Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600 @error('email') border-red-500 @enderror">
                    @error('email')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="nip" class="block text-sm font-medium text-gray-700 mb-1">NIP (Opsional)</label>
                    <input type="text" name="nip" id="nip" value="{{ old('nip', $user->nip) }}" class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600 @error('nip') border-red-500 @enderror">
                    @error('nip')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="pangkat_golongan" class="block text-sm font-medium text-gray-700 mb-1">Pangkat / Golongan (Opsional)</label>
                    <input type="text" name="pangkat_golongan" id="pangkat_golongan" value="{{ old('pangkat_golongan', $user->pangkat_golongan) }}" class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600 @error('pangkat_golongan') border-red-500 @enderror">
                    @error('pangkat_golongan')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="jabatan" class="block text-sm font-medium text-gray-700 mb-1">Jabatan (Opsional)</label>
                    <input type="text" name="jabatan" id="jabatan" value="{{ old('jabatan', $user->jabatan) }}" class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600 @error('jabatan') border-red-500 @enderror">
                    @error('jabatan')
                    <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="pt-2">
                <h4 class="text-sm font-medium text-gray-900 mb-2">Ganti Kata Sandi (Opsional)</h4>
                <p class="text-xs text-gray-500 mb-4">Kosongkan kolom di bawah jika Anda tidak ingin mengubah kata sandi.</p>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Kata Sandi Baru</label>
                        <input type="password" name="password" id="password" class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600 @error('password') border-red-500 @enderror">
                        @error('password')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Konfirmasi Kata Sandi Baru</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600">
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-4 space-x-3 mt-6">
                <button type="submit" class="inline-flex h-9 items-center justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-indigo-600">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
