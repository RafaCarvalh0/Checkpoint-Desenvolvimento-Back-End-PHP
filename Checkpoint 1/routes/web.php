<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('products.index')
        : redirect()->route('login');
});

Route::pattern('product', '[0-9]+');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])
        ->middleware('throttle:auth-actions')
        ->name('login.store');
    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware('throttle:auth-actions')
        ->name('register.store');
});

Route::post('/logout', [SessionController::class, 'destroy'])
    ->middleware(['auth', 'throttle:auth-actions'])
    ->name('logout');

Route::resource('products', ProductController::class)
    ->only(['index', 'show']);

Route::resource('products', ProductController::class)
    ->except(['index', 'show'])
    ->middleware(['auth', 'throttle:product-writes']);
