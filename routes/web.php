<?php

use App\Http\Controllers\CryptomusNotificationController;
use App\Http\Controllers\MidtransNotificationController;
use App\Livewire\CartPage;
use App\Livewire\Catalog;
use App\Livewire\CheckoutPage;
use App\Livewire\DownloadCenter;
use App\Livewire\OrderReceipt;
use App\Livewire\ProductDetail;
use App\Livewire\SubscriptionsPage;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', Catalog::class)->name('catalog');

Route::get('/products/{product}', ProductDetail::class)->name('product-detail');

Route::get('/cart', CartPage::class)->name('cart');

Route::get('/checkout', CheckoutPage::class)->name('checkout');

Route::get('/orders/{order}', OrderReceipt::class)->name('orders.show');

Route::get('/downloads', DownloadCenter::class)->name('downloads');

Route::get('/subscriptions', SubscriptionsPage::class)->name('subscriptions');

Route::post('/midtrans/notification', MidtransNotificationController::class)
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('midtrans.notification');

Route::post('/cryptomus/notification', CryptomusNotificationController::class)
    ->withoutMiddleware([ValidateCsrfToken::class])
    ->name('cryptomus.notification');
