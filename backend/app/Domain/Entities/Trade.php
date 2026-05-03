<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\ValueObjects\Amount;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\Price;
use App\Enums\Symbol;

final class Trade
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $buyOrderId,
        private readonly int $sellOrderId,
        private readonly int $buyerUserId,
        private readonly int $sellerUserId,
        private readonly Symbol $symbol,
        private readonly Price $price,
        private readonly Amount $amount,
        private readonly Money $volume,
        private readonly Money $fee,
    ) {}

    public function id(): ?int
    {
        return $this->id;
    }

    public function buyOrderId(): int
    {
        return $this->buyOrderId;
    }

    public function sellOrderId(): int
    {
        return $this->sellOrderId;
    }

    public function buyerUserId(): int
    {
        return $this->buyerUserId;
    }

    public function sellerUserId(): int
    {
        return $this->sellerUserId;
    }

    public function symbol(): Symbol
    {
        return $this->symbol;
    }

    public function price(): Price
    {
        return $this->price;
    }

    public function amount(): Amount
    {
        return $this->amount;
    }

    public function volume(): Money
    {
        return $this->volume;
    }

    public function fee(): Money
    {
        return $this->fee;
    }
}
