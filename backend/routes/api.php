<?php

use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', ProfileController::class)->name('profile');
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
});