<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\SectionProductController;

Route::get('/products', ProductController::class)->name('products.index');
Route::get('/products/{section}', ProductController::class)->name('section_products.index');
