<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\PerkaraController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');

    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Modul Perkara
    Route::get('/perkara', [PerkaraController::class, 'index'])->name('perkara.index');
    Route::get('/perkara/create', [PerkaraController::class, 'create'])->name('perkara.create');
    Route::post('/perkara', [PerkaraController::class, 'store'])->name('perkara.store');
    Route::get('/perkara/{id}', [PerkaraController::class, 'show'])->name('perkara.show');
    Route::post('/perkara/{id}/perkembangan', [PerkaraController::class, 'catatPerkembangan'])->name('perkara.catatPerkembangan');
});