<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Symbol;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Trade extends Model
{
    protected $fillable = [
        'buy_order_id',
        'sell_order_id',
        'buyer_user_id',
        'seller_user_id',
        'symbol',
        'price',
        'amount',
        'volume',
        'fee',
    ];

    protected function casts(): array
    {
        return [
            'symbol' => Symbol::class,
            'price' => 'integer',
            'amount' => 'integer',
            'volume' => 'integer',
            'fee' => 'integer',
        ];
    }

    public function buyOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'buy_order_id');
    }

    public function sellOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'sell_order_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }
}