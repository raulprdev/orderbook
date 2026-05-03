<?php

declare(strict_types=1);

namespace App\Domain\Entities;

use App\Domain\Exceptions\InvalidOrder;
use App\Domain\Exceptions\InvalidOrderTransition;
use App\Domain\ValueObjects\Amount;
use App\Domain\ValueObjects\Price;
use App\Enums\OrderStatus;
use App\Enums\Side;
use App\Enums\Symbol;

final class Order
{
    public function __construct(
        private readonly ?int $id,
        private readonly int $userId,
        private readonly Symbol $symbol,
        private readonly Side $side,
        private readonly Price $price,
        private readonly Amount $amount,
        private OrderStatus $status,
    ) {
        if ($amount->subunit() === 0) {
            throw new InvalidOrder('Order amount must be strictly positive');
        }
    }

    public function fill(): void
    {
        $this->transitionTo(OrderStatus::Filled);
    }

    public function cancel(): void
    {
        $this->transitionTo(OrderStatus::Cancelled);
    }

    private function transitionTo(OrderStatus $next): void
    {
        if (! $this->status->canTransitionTo($next)) {
            throw new InvalidOrderTransition(
                sprintf('Cannot transition from %s to %s', $this->status->value, $next->value)
            );
        }

        $this->status = $next;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function symbol(): Symbol
    {
        return $this->symbol;
    }

    public function side(): Side
    {
        return $this->side;
    }

    public function price(): Price
    {
        return $this->price;
    }

    public function amount(): Amount
    {
        return $this->amount;
    }

    public function status(): OrderStatus
    {
        return $this->status;
    }
}