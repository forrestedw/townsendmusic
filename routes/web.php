<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductsController;

//
//Route::get('/products/{section}', )->name('section.index');
//
Route::get('/products', [ProductsController::class, 'index']);
Route::get('/old', [ProductsController::class, 'old']);
