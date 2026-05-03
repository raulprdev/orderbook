<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\InvalidMoney;

final class Money
{
    private const SCALE = 1_000_000;

    private function __construct(private readonly int $microUsd)
    {
    }

    public static function fromMicroUsd(int $microUsd): self
    {
        if ($microUsd < 0) {
            throw InvalidMoney::negative($microUsd);
        }

        return new self($microUsd);
    }

    public static function fromUsd(string $usd): self
    {
        if (! preg_match('/^(\d+)(?:\.(\d{1,6}))?$/', $usd, $matches)) {
            throw InvalidMoney::malformedString($usd);
        }

        $whole = (int) $matches[1];
        $fraction = isset($matches[2]) ? str_pad($matches[2], 6, '0', STR_PAD_RIGHT) : '000000';

        return self::fromMicroUsd($whole * self::SCALE + (int) $fraction);
    }

    public function microUsd(): int
    {
        return $this->microUsd;
    }

    public function toUsd(): string
    {
        $whole = intdiv($this->microUsd, self::SCALE);
        $fraction = $this->microUsd % self::SCALE;

        if ($fraction === 0) {
            return sprintf('%d.00', $whole);
        }

        $fractionStr = rtrim(str_pad((string) $fraction, 6, '0', STR_PAD_LEFT), '0');
        if (strlen($fractionStr) < 2) {
            $fractionStr = str_pad($fractionStr, 2, '0', STR_PAD_RIGHT);
        }

        return sprintf('%d.%s', $whole, $fractionStr);
    }

    public function plus(self $other): self
    {
        return self::fromMicroUsd($this->microUsd + $other->microUsd);
    }

    public function minus(self $other): self
    {
        return self::fromMicroUsd($this->microUsd - $other->microUsd);
    }

    public function equals(self $other): bool
    {
        return $this->microUsd === $other->microUsd;
    }
}
