<?php

use Illuminate\Support\Facades\Route;
use Haybea\Trashcan\Http\Controllers\TrashcanController;

// Dashboard
Route::get('/', [TrashcanController::class, 'index'])->name('trashcan.index');

// Statistics
Route::get('/statistics', [TrashcanController::class, 'statistics'])->name('trashcan.statistics');

// Activity Log
Route::get('/activity', [TrashcanController::class, 'activity'])->name('trashcan.activity');

// Model routes
// Static-segment routes (bulk-restore, bulk-delete, empty, export, affected-children)
// must be registered before the /{id} wildcards below, otherwise Laravel's
// router matches the wildcard first and these actions become unreachable
// (e.g. DELETE .../empty would match DELETE /{id} with id="empty").
Route::prefix('model/{model}')->group(function () {
    Route::get('/', [TrashcanController::class, 'show'])->name('trashcan.show');
    Route::get('/affected-children', [TrashcanController::class, 'getAffectedChildren'])->name('trashcan.affected-children');
    Route::post('/bulk-restore', [TrashcanController::class, 'bulkRestore'])->name('trashcan.bulk-restore');
    // Accept both DELETE (spoofed via forms) and POST (e.g. from JS clients) to avoid 404s when method spoofing isn't applied.
    Route::match(['delete', 'post'], '/bulk-delete', [TrashcanController::class, 'bulkForceDelete'])->name('trashcan.bulk-force-delete');
    Route::delete('/empty', [TrashcanController::class, 'emptyTrash'])->name('trashcan.empty-trash');
    Route::get('/export', [TrashcanController::class, 'export'])->name('trashcan.export');
    Route::post('/{id}/restore', [TrashcanController::class, 'restore'])->name('trashcan.restore');
    Route::delete('/{id}', [TrashcanController::class, 'forceDelete'])->name('trashcan.force-delete');
});