<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\InvalidPrice;
use Throwable;

final class Price
{
    use PositiveScalar;

    private const SCALE = 2;

    public static function fromCent(int $cent): self
    {
        return self::fromInt($cent);
    }

    public static function fromUsd(string $usd): self
    {
        return self::fromDecimalString($usd);
    }

    public function cent(): int
    {
        return $this->value();
    }

    public function toUsd(): string
    {
        return $this->toDecimalString(self::SCALE);
    }

    protected static function scale(): int
    {
        return self::SCALE;
    }

    protected static function additionalChecks(int $value): void
    {
        if ($value === 0) {
            throw new InvalidPrice('Price must be strictly positive');
        }
    }

    protected static function invalidValueException(string $message): Throwable
    {
        return new InvalidPrice($message);
    }
}