<?php

use Illuminate\Support\Facades\Route;
use Haybea\Trashcan\Http\Controllers\TrashcanController;

Route::get('/', [TrashcanController::class, 'index'])->name('trashcan.index');
Route::get('/statistics', [TrashcanController::class, 'statistics'])->name('trashcan.statistics');
Route::get('/activity', [TrashcanController::class, 'activity'])->name('trashcan.activity');

Route::prefix('model/{model}')->group(function () {
    Route::get('/', [TrashcanController::class, 'show'])->name('trashcan.show');
    Route::post('/{id}/restore', [TrashcanController::class, 'restore'])->name('trashcan.restore');
    Route::delete('/{id}', [TrashcanController::class, 'forceDelete'])->name('trashcan.force-delete');
    Route::post('/bulk-restore', [TrashcanController::class, 'bulkRestore'])->name('trashcan.bulk-restore');
    Route::delete('/bulk-delete', [TrashcanController::class, 'bulkForceDelete'])->name('trashcan.bulk-force-delete');
    Route::delete('/empty', [TrashcanController::class, 'emptyTrash'])->name('trashcan.empty-trash');
    Route::get('/export', [TrashcanController::class, 'export'])->name('trashcan.export');
});