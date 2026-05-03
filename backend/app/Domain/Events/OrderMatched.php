<?php

declare(strict_types=1);

namespace App\Domain\Events;

use App\Domain\ValueObjects\Amount;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\Price;
use App\Enums\Symbol;

final class OrderMatched
{
    public function __construct(
        public readonly int $buyOrderId,
        public readonly int $sellOrderId,
        public readonly int $buyerUserId,
        public readonly int $sellerUserId,
        public readonly Symbol $symbol,
        public readonly Price $matchPrice,
        public readonly Amount $matchAmount,
        public readonly Money $volume,
        public readonly Money $fee,
    ) {}
}
