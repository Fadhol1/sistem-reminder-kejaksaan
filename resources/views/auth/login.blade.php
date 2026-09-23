@extends('layouts.guest')

@section('title', 'Masuk - SIPETA')

@section('content')
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-900">Masuk ke akun</h2>
        <p class="text-sm text-gray-500 mt-1">Silakan masuk untuk mengelola perkara dan reminder.</p>
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

    <form method="POST" action="{{ route('login.post') }}">
        @csrf

        <div class="mb-4">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                class="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-md text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] transition"
                placeholder="nama@kejaksaan.go.id">
            @error('email')
                <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div class="mb-5">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Kata Sandi</label>
            <input id="password" type="password" name="password" required
                class="w-full px-3.5 py-2.5 bg-white border border-gray-300 rounded-md text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:border-[#f53003] focus:ring-1 focus:ring-[#f53003] transition"
                placeholder="••••••••">
            @error('password')
                <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
            @enderror
        </div>

        <div class="flex items-center justify-between mb-6">
            <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                <input type="checkbox" name="remember" class="rounded border-gray-300 text-[#f53003] focus:ring-[#f53003]">
                Ingat saya
            </label>
        </div>

        <button type="submit"
            class="w-full px-5 py-2.5 bg-[#1b1b18] hover:bg-black text-white rounded-md text-sm font-medium transition">
            Masuk
        </button>

        <p class="text-center text-sm text-gray-600 mt-5">
            Belum punya akun?
            <a href="{{ route('register') }}" class="text-[#f53003] hover:underline font-medium">Daftar di sini</a>
        </p>
    </form>
@endsection