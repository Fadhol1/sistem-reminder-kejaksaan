@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="space-y-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Selamat datang, {{ auth()->user()->name }}</h3>
            <p class="text-sm text-gray-500 mt-1">Ringkasan perkara dan reminder yang perlu perhatian Anda.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white border border-gray-200 rounded-lg p-5">
                <p class="text-sm text-gray-500">Total Perkara</p>
                <p class="text-3xl font-semibold text-gray-900 mt-1">-</p>
                <p class="text-xs text-gray-400 mt-2">Belum terhubung ke data</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-5">
                <p class="text-sm text-gray-500">Reminder Aktif</p>
                <p class="text-3xl font-semibold text-gray-900 mt-1">-</p>
                <p class="text-xs text-gray-400 mt-2">Belum terhubung ke data</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-5">
                <p class="text-sm text-gray-500">Mendesak (H-3)</p>
                <p class="text-3xl font-semibold text-amber-600 mt-1">-</p>
                <p class="text-xs text-gray-400 mt-2">Belum terhubung ke data</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-5">
                <p class="text-sm text-gray-500">Overdue</p>
                <p class="text-3xl font-semibold text-red-600 mt-1">-</p>
                <p class="text-xs text-gray-400 mt-2">Belum terhubung ke data</p>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <h4 class="text-sm font-semibold text-gray-900 mb-1">Konten Dashboard</h4>
            <p class="text-sm text-gray-500">Halaman ini siap diisi dengan tabel perkara, daftar reminder, dan grafik. Konten akan dibuat pada tahap berikutnya.</p>
        </div>
    </div>
@endsection