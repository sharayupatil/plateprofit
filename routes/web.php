<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PosImportController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('/pos-imports', [
        PosImportController::class,
        'index',
    ])->name('pos-imports.index');

    Route::get('/pos-imports/create', [
        PosImportController::class,
        'create',
    ])->name('pos-imports.create');

    Route::post('/pos-imports', [
        PosImportController::class,
        'store',
    ])->name('pos-imports.store');
});
