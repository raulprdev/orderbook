<?php

declare(strict_types=1);

namespace App\Domain\Exceptions;

final class InvalidMoney extends DomainException
{
    public static function negative(int $microUsd): self
    {
        return new self(sprintf('Money cannot be negative: %d micro-USD', $microUsd));
    }

    public static function malformedString(string $input): self
    {
        return new self(sprintf('Cannot parse "%s" as a USD value', $input));
    }
}
