<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\InsufficientBalance;
use App\Domain\ValueObjects\Money;

final class User
{
    public function __construct(
        private readonly int $id,
        private Money $balance,
    ) {}

    public function debit(Money $amount): void
    {
        $this->ensureBalanceCovers($amount);

        $this->balance = $this->balance->minus($amount);
    }

    public function credit(Money $amount): void
    {
        $this->balance = $this->balance->plus($amount);
    }

    private function ensureBalanceCovers(Money $amount): void
    {
        if ($this->balance->microUsd() < $amount->microUsd()) {
            throw new InsufficientBalance(sprintf(
                'User %d balance has %d micro-USD, requested %d',
                $this->id,
                $this->balance->microUsd(),
                $amount->microUsd(),
            ));
        }
    }

    public function id(): int
    {
        return $this->id;
    }

    public function balance(): Money
    {
        return $this->balance;
    }
}
