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

    // Profile Endpoint (recommended for mobile)
    Route::get('/profile', [AuthController::class, 'getProfile'])->name('api.profile.get');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('api.profile.update');
    Route::post('/profile', [AuthController::class, 'updateProfile'])->name('api.profile.update.post'); // POST alias (for multipart/form-data with foto)

    // Change Password (requires current_password verification)
    Route::post('/password', [AuthController::class, 'changePassword'])->name('api.password.change');

    // Perkara
    Route::prefix('perkara')->group(function () {
        Route::get('/', [PerkaraApiController::class, 'index'])->name('api.perkara.index');
        Route::post('/', [PerkaraApiController::class, 'store'])->name('api.perkara.store');
        Route::post('/timeline', [PerkaraApiController::class, 'storeTimeline'])->name('api.perkara.timeline.store');
        Route::get('/{id}', [PerkaraApiController::class, 'show'])->name('api.perkara.show');
        Route::get('/{id}/timeline', [PerkaraApiController::class, 'getTimelines'])->name('api.perkara.timeline.index');
        Route::get('/{id}/timelines', [PerkaraApiController::class, 'getTimelines']);
        Route::post('/{id}/timeline', [PerkaraApiController::class, 'storeTimeline'])->name('api.perkara.timeline.store_with_id');
        Route::delete('/{id}', [PerkaraApiController::class, 'destroy']);
        
        // Future Android Action Hooks
        Route::get('/{id}/available-actions', [PerkaraApiController::class, 'getAvailableActions'])->name('api.perkara.available_actions');
        Route::post('/{id}/actions', [PerkaraApiController::class, 'processAction'])->name('api.perkara.process_action');
    });

    // Timeline Global Audit Trail
    Route::get('/timelines', [PerkaraApiController::class, 'getAllTimelines'])->name('api.timelines.index');

    // Reminders & Reminder Logs (Audit Trail)
    Route::get('/reminders', [PerkaraApiController::class, 'getReminders'])->name('api.reminders.index');
    Route::get('/reminders/logs', [PerkaraApiController::class, 'getReminderLogs'])->name('api.reminders.logs');
    Route::get('/reminder-logs', [PerkaraApiController::class, 'getReminderLogs'])->name('api.reminders.logs.alias');
});
