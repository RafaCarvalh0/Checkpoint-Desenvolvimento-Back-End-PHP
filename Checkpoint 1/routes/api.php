<?php

use App\Http\Controllers\Api\DocumentationController;
use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/docs', [DocumentationController::class, 'index'])->name('api.docs');
Route::get('/docs/openapi.yaml', [DocumentationController::class, 'specification'])->name('api.docs.openapi');

Route::prefix('v1')
    ->name('api.v1.')
    ->group(function (): void {
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    });
