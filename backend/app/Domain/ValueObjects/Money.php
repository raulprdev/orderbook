<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\InvalidMoney;
use Throwable;

final class Money
{
    use PositiveScalar;

    private const SCALE = 6;

    private const DISPLAY_DECIMALS = 2;

    public static function fromMicroUsd(int $microUsd): self
    {
        return self::fromInt($microUsd);
    }

    public static function fromUsd(string $usd): self
    {
        return self::fromDecimalString($usd);
    }

    public function microUsd(): int
    {
        return $this->value();
    }

    public function toUsd(): string
    {
        return $this->toDecimalString(self::DISPLAY_DECIMALS);
    }

    protected static function scale(): int
    {
        return self::SCALE;
    }

    protected static function invalidValueException(string $message): Throwable
    {
        return new InvalidMoney($message);
    }
}
