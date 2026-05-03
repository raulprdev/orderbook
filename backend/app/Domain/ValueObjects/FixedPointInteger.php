<?php

declare(strict_types=1);

namespace App\Domain\ValueObjects;

use Throwable;

trait FixedPointInteger
{
    private function __construct(private readonly int $value)
    {
    }

    final public static function fromInt(int $value): static
    {
        if ($value < 0) {
            throw static::invalidValueException(
                sprintf('Value cannot be negative: %d', $value)
            );
        }

        return new static($value);
    }

    final public static function fromDecimalString(string $input): static
    {
        $scale = static::scale();
        $pattern = '/^(\d+)(?:\.(\d{1,'.$scale.'}))?$/';

        if (! preg_match($pattern, $input, $matches)) {
            throw static::invalidValueException(
                sprintf('Cannot parse "%s" as decimal value', $input)
            );
        }

        $whole = (int) $matches[1];
        $fraction = isset($matches[2])
            ? str_pad($matches[2], $scale, '0', STR_PAD_RIGHT)
            : str_repeat('0', $scale);

        return static::fromInt($whole * (10 ** $scale) + (int) $fraction);
    }

    final public function value(): int
    {
        return $this->value;
    }

    final public function toDecimalString(int $minDecimals): string
    {
        $scale = static::scale();
        $factor = 10 ** $scale;
        $whole = intdiv($this->value, $factor);
        $fraction = $this->value % $factor;

        if ($fraction === 0) {
            $fractionStr = str_repeat('0', max($minDecimals, 0));
        } else {
            $fractionStr = rtrim(str_pad((string) $fraction, $scale, '0', STR_PAD_LEFT), '0');
            if (strlen($fractionStr) < $minDecimals) {
                $fractionStr = str_pad($fractionStr, $minDecimals, '0', STR_PAD_RIGHT);
            }
        }

        return $fractionStr === ''
            ? (string) $whole
            : sprintf('%d.%s', $whole, $fractionStr);
    }

    final public function plus(self $other): static
    {
        return static::fromInt($this->value + $other->value);
    }

    final public function minus(self $other): static
    {
        return static::fromInt($this->value - $other->value);
    }

    final public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    abstract protected static function scale(): int;

    abstract protected static function invalidValueException(string $message): Throwable;
}
