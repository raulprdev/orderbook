<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Domain\Entities\Order;
use App\Enums\Symbol;

interface OrderRepository
{
    public function save(Order $order): Order;

    public function findById(int $id): ?Order;

    public function findOpenForUpdate(int $id): ?Order;

    public function firstMatchableCounterOrder(Order $incoming): ?Order;

    /**
     * @return array<int, Order>
     */
    public function openOrdersForSymbol(Symbol $symbol): array;

    /**
     * @return array<int, Order>
     */
    public function userOrders(int $userId): array;
}
