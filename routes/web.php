<?php

use App\Livewire\CartPage;
use App\Livewire\Catalog;
use App\Livewire\CheckoutPage;
use App\Livewire\OrderReceipt;
use App\Livewire\ProductDetail;
use Illuminate\Support\Facades\Route;

Route::get('/', Catalog::class)->name('catalog');

Route::get('/products/{product}', ProductDetail::class)->name('product-detail');

Route::get('/cart', CartPage::class)->name('cart');

Route::get('/checkout', CheckoutPage::class)->name('checkout');

Route::get('/orders/{order}', OrderReceipt::class)->name('orders.show');
