<?php

declare(strict_types=1);

namespace App\Domain\Matching;

use App\Domain\Entities\Order;
use App\Repositories\Contracts\OrderRepository;

final class FirstValidMatchStrategy implements MatchingStrategy
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
    ) {}

    public function findMatch(Order $incoming): ?Order
    {
        return $this->orderRepository->firstMatchableCounterOrder($incoming);
    }
}
