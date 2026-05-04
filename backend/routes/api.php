<?php

use App\Http\Controllers\CancelOrderController;
use App\Http\Controllers\MyOrdersController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', ProfileController::class)->name('profile');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/mine', MyOrdersController::class)->name('orders.mine');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::post('/orders/{order}/cancel', CancelOrderController::class)
        ->where('order', '[0-9]+')
        ->name('orders.cancel');
});