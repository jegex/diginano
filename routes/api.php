<?php

use App\Http\Controllers\LicenseController;
use Illuminate\Support\Facades\Route;

Route::prefix('licenses')->middleware(['throttle:api'])->group(function (): void {
    Route::post('activate', [LicenseController::class, 'activate'])->name('licenses.activate');
    Route::post('validate', [LicenseController::class, 'validate'])->name('licenses.validate');
    Route::post('revoke', [LicenseController::class, 'revoke'])->name('licenses.revoke');
});
