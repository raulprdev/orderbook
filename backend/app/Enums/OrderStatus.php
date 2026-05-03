<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderStatus: string
{
    case Open = 'open';
    case Filled = 'filled';
    case Cancelled = 'cancelled';

    public function isOpen(): bool
    {
        return $this === self::Open;
    }

    public function isCompleted(): bool
    {
        return ! $this->isOpen();
    }

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Open => $next === self::Filled || $next === self::Cancelled,
            self::Filled, self::Cancelled => false,
        };
    }
}
