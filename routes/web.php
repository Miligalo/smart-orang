<?php

use App\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('import.index'));

Route::prefix('import')->name('import.')->group(function () {
    Route::get('/', [ImportController::class, 'index'])->name('index');
    Route::post('/', [ImportController::class, 'store'])->name('store');
    Route::get('/{import}', [ImportController::class, 'status'])->name('status');
    Route::get('/{import}/json', [ImportController::class, 'statusJson'])->name('status.json');
});
