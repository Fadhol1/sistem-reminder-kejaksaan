<?php

use App\Http\Controllers\Api\PerkaraApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Sistem Reminder Perkara Kejaksaan
|--------------------------------------------------------------------------
|
| Prefix default: /api (didaftarkan di bootstrap/app.php)
|
*/

Route::prefix('perkara')->group(function () {
    // 1. Index: Daftar semua perkara dengan relasi timeline terakhir & reminder aktif
    Route::get('/', [PerkaraApiController::class, 'index'])->name('api.perkara.index');

    // 3. Store: Tambah data perkara baru beserta inisialisasi awal
    Route::post('/', [PerkaraApiController::class, 'store'])->name('api.perkara.store');

    // 4. Store Timeline: Tambah perkembangan linimasa via body (dengan perkara_id)
    Route::post('/timeline', [PerkaraApiController::class, 'storeTimeline'])->name('api.perkara.timeline.store');

    // 2. Show: Detail perkara lengkap dengan seluruh riwayat timeline & reminder
    Route::get('/{id}', [PerkaraApiController::class, 'show'])->name('api.perkara.show');

    // 4. Store Timeline: Tambah perkembangan linimasa via route parameter ID
    Route::post('/{id}/timeline', [PerkaraApiController::class, 'storeTimeline'])->name('api.perkara.timeline.store_with_id');

    // 5. DESTROY: Hapus perkara berdasarkan ID (TAMBAHKAN INI)
    Route::delete('/{id}', [PerkaraApiController::class, 'destroy']);

    // 6. FUTURE ANDROID API: Get Available Actions based on State Machine
    Route::get('/{id}/available-actions', [PerkaraApiController::class, 'getAvailableActions'])->name('api.perkara.available_actions');

    // 7. FUTURE ANDROID API: Execute Action
    Route::post('/{id}/actions', [PerkaraApiController::class, 'processAction'])->name('api.perkara.process_action');
});

// 5. Get Reminders: Daftar reminder aktif & mendesak untuk dashboard Android kepala
Route::get('/reminders', [PerkaraApiController::class, 'getReminders'])->name('api.reminders.index');
