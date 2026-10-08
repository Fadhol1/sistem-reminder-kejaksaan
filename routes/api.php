<?php

use App\Http\Controllers\Api\PerkaraApiController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes - Sistem Reminder Perkara Kejaksaan
|--------------------------------------------------------------------------
*/

// Public Endpoints
Route::post('/login', [AuthController::class, 'login'])->name('api.login');

// Protected Endpoints
Route::middleware('auth:sanctum')->group(function () {
    
    // Auth & Profile
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/user', [AuthController::class, 'user'])->name('api.user');
    Route::post('/profile', [AuthController::class, 'updateProfile'])->name('api.profile.update');

    // Perkara
    Route::prefix('perkara')->group(function () {
        Route::get('/', [PerkaraApiController::class, 'index'])->name('api.perkara.index');
        Route::post('/', [PerkaraApiController::class, 'store'])->name('api.perkara.store');
        Route::post('/timeline', [PerkaraApiController::class, 'storeTimeline'])->name('api.perkara.timeline.store');
        Route::get('/{id}', [PerkaraApiController::class, 'show'])->name('api.perkara.show');
        Route::post('/{id}/timeline', [PerkaraApiController::class, 'storeTimeline'])->name('api.perkara.timeline.store_with_id');
        Route::delete('/{id}', [PerkaraApiController::class, 'destroy']);
        
        // Future Android Action Hooks
        Route::get('/{id}/available-actions', [PerkaraApiController::class, 'getAvailableActions'])->name('api.perkara.available_actions');
        Route::post('/{id}/actions', [PerkaraApiController::class, 'processAction'])->name('api.perkara.process_action');
    });

    // Reminders
    Route::get('/reminders', [PerkaraApiController::class, 'getReminders'])->name('api.reminders.index');
});
