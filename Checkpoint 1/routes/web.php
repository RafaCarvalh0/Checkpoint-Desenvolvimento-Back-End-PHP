<?php

use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
});

Route::pattern('product', '[0-9]+');
Route::resource('products', ProductController::class);
