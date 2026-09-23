@extends('layouts.guest')

@section('title', 'Daftar Akun - SIPETA')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-900">Buat akun baru</h2>
        <p class="text-sm text-gray-500 mt-1">Daftar untuk mulai mengelola perkara dan reminder.</p>
    </div>

    @if ($errors->any())
        <div class="mb-4 px-3.5 py-2.5 rounded-md bg-red-50 border border-red-200 text-sm text-red-700">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register.post') }}">
        @csrf

        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">Nama Lengkap</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                class="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-md text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] transition"
                placeholder="Nama lengkap sesuai NIP">
            @error('name')
                <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                class="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-md text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] transition"
                placeholder="nama@kejaksaan.go.id">
            @error('email')
                <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="role" class="block text-sm font-medium text-gray-700 mb-1.5">Peran</label>
            <select id="role" name="role" required
                class="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-md text-sm text-gray-900 focus:outline-none focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] transition">
                <option value="penyidik" {{ old('role') === 'penyidik' ? 'selected' : '' }}>Penyidik</option>
                <option value="jaksa" {{ old('role') === 'jaksa' ? 'selected' : '' }}>Jaksa</option>
                <option value="pidum" {{ old('role') === 'pidum' ? 'selected' : '' }}>Pidum (Kepala)</option>
            </select>
            @error('role')
                <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-4">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Kata Sandi</label>
            <input id="password" type="password" name="password" required
                class="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-md text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] transition"
                placeholder="Minimal 8 karakter">
            @error('password')
                <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-6">
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">Konfirmasi Kata Sandi</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required
                class="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-md text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] transition"
                placeholder="Ulangi kata sandi">
        </div>

        <button type="submit"
            class="w-full px-5 py-2.5 bg-[#1b1b18] hover:bg-black text-white rounded-md text-sm font-medium transition">
            Daftar
        </button>

        <p class="text-center text-sm text-gray-600 mt-5">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="text-[#f53003] hover:underline font-medium">Masuk di sini</a>
        </p>
    </form>
@endsection