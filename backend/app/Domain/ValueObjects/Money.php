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

    private const VOLUME_DIVISOR = 10_000;

    private const BASIS_POINTS_DENOMINATOR = 10_000;

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

    public static function fromVolume(Price $price, Amount $amount): self
    {
        return self::fromMicroUsd(intdiv($price->cent() * $amount->subunit(), self::VOLUME_DIVISOR));
    }

    public function applyBasisPoints(int $basisPoints): self
    {
        return self::fromMicroUsd(intdiv($this->microUsd() * $basisPoints, self::BASIS_POINTS_DENOMINATOR));
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
