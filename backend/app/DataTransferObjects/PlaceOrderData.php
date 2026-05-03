<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use Spatie\LaravelData\Data;

final class PlaceOrderData extends Data
{
    public function __construct(
        public readonly string $symbol,
        public readonly string $side,
        public readonly string $price,
        public readonly string $amount,
    ) {}
}