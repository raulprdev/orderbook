<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\InsufficientAsset;
use App\Domain\ValueObjects\Amount;
use App\Enums\Symbol;

final class Asset
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $userId,
        private readonly Symbol $symbol,
        private Amount $amount,
        private Amount $lockedAmount,
    ) {}

    public function lock(Amount $amount): void
    {
        $this->ensureAvailableCovers($amount);

        $this->amount = $this->amount->minus($amount);
        $this->lockedAmount = $this->lockedAmount->plus($amount);
    }

    public function release(Amount $amount): void
    {
        $this->ensureLockedCovers($amount);

        $this->lockedAmount = $this->lockedAmount->minus($amount);
        $this->amount = $this->amount->plus($amount);
    }

    public function debit(Amount $amount): void
    {
        $this->ensureLockedCovers($amount);

        $this->lockedAmount = $this->lockedAmount->minus($amount);
    }

    public function credit(Amount $amount): void
    {
        $this->amount = $this->amount->plus($amount);
    }

    private function ensureAvailableCovers(Amount $requested): void
    {
        if ($this->amount->subunit() < $requested->subunit()) {
            throw new InsufficientAsset(sprintf(
                'Asset %s available pool has %d subunit, requested %d',
                $this->symbol->value,
                $this->amount->subunit(),
                $requested->subunit(),
            ));
        }
    }

    private function ensureLockedCovers(Amount $requested): void
    {
        if ($this->lockedAmount->subunit() < $requested->subunit()) {
            throw new InsufficientAsset(sprintf(
                'Asset %s locked pool has %d subunit, requested %d',
                $this->symbol->value,
                $this->lockedAmount->subunit(),
                $requested->subunit(),
            ));
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function symbol(): Symbol
    {
        return $this->symbol;
    }

    public function amount(): Amount
    {
        return $this->amount;
    }

    public function lockedAmount(): Amount
    {
        return $this->lockedAmount;
    }
}
