<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buy_order_id')->constrained('orders');
            $table->foreignId('sell_order_id')->constrained('orders');
            $table->foreignId('buyer_user_id')->constrained('users');
            $table->foreignId('seller_user_id')->constrained('users');
            $table->string('symbol', 10);
            $table->bigInteger('price');
            $table->bigInteger('amount');
            $table->bigInteger('volume');
            $table->bigInteger('fee');
            $table->timestamps();

            $table->index('buyer_user_id');
            $table->index('seller_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};