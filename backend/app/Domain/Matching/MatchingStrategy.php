<?php

declare(strict_types=1);

namespace App\Domain\Matching;

use App\Domain\Entities\Order;

interface MatchingStrategy
{
    public function findMatch(Order $incoming): ?Order;
}