<?php

use App\Livewire\Catalog;
use App\Livewire\ProductDetail;
use Illuminate\Support\Facades\Route;

Route::get('/', Catalog::class)->name('catalog');

Route::get('/products/{product}', ProductDetail::class)->name('product-detail');
