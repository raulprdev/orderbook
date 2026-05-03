<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\InvalidAmount;
use Throwable;

final class Amount
{
    use PositiveScalar;

    private const SCALE = 8;

    public static function fromSubunit(int $subunit): self
    {
        return self::fromInt($subunit);
    }

    public static function fromDecimal(string $decimal): self
    {
        return self::fromDecimalString($decimal);
    }

    public function subunit(): int
    {
        return $this->value();
    }

    public function toDecimal(): string
    {
        return $this->toDecimalString(self::SCALE);
    }

    protected static function scale(): int
    {
        return self::SCALE;
    }

    protected static function invalidValueException(string $message): Throwable
    {
        return new InvalidAmount($message);
    }
}
