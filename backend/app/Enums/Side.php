<?php

declare(strict_types=1);

namespace App\Enums;

enum Side: string
{
    case Buy = 'buy';
    case Sell = 'sell';

    public function opposite(): self
    {
        return match ($this) {
            self::Buy => self::Sell,
            self::Sell => self::Buy,
        };
    }
}
