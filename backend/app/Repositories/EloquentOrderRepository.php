<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Entities\Order as DomainOrder;
use App\Domain\ValueObjects\Amount;
use App\Domain\ValueObjects\Price;
use App\Enums\OrderStatus;
use App\Enums\Side;
use App\Enums\Symbol;
use App\Models\Order as EloquentOrder;
use App\Repositories\Contracts\OrderRepository;

final class EloquentOrderRepository implements OrderRepository
{
    public function save(DomainOrder $order): DomainOrder
    {
        $eloquent = $order->id() === null
            ? new EloquentOrder
            : EloquentOrder::findOrFail($order->id());

        $this->applyToEloquent($order, $eloquent);
        $eloquent->save();

        return $this->toDomain($eloquent);
    }

    public function findById(int $id): ?DomainOrder
    {
        return $this->toDomain(EloquentOrder::find($id));
    }

    public function findOpenForUpdate(int $id): ?DomainOrder
    {
        return $this->toDomain(
            EloquentOrder::query()
                ->where('id', $id)
                ->where('status', OrderStatus::Open)
                ->lockForUpdate()
                ->first()
        );
    }

    public function firstMatchableCounterOrder(DomainOrder $incoming): ?DomainOrder
    {
        $query = EloquentOrder::query()
            ->where('symbol', $incoming->symbol())
            ->where('side', $incoming->side()->opposite())
            ->where('status', OrderStatus::Open)
            ->where('amount', $incoming->amount()->subunit())
            ->where('user_id', '!=', $incoming->userId());

        if ($incoming->side() === Side::Buy) {
            $query->where('price', '<=', $incoming->price()->cent());
        } else {
            $query->where('price', '>=', $incoming->price()->cent());
        }

        return $this->toDomain(
            $query->orderBy('created_at')->lockForUpdate()->first()
        );
    }

    public function openOrdersForSymbol(Symbol $symbol): array
    {
        return EloquentOrder::query()
            ->where('symbol', $symbol)
            ->where('status', OrderStatus::Open)
            ->orderBy('created_at')
            ->get()
            ->map(fn (EloquentOrder $o) => $this->toDomain($o))
            ->all();
    }

    public function userOrders(int $userId): array
    {
        return EloquentOrder::query()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn (EloquentOrder $o) => $this->toDomain($o))
            ->all();
    }

    private function toDomain(?EloquentOrder $eloquent): ?DomainOrder
    {
        if ($eloquent === null) {
            return null;
        }

        return new DomainOrder(
            id: $eloquent->id,
            userId: $eloquent->user_id,
            symbol: $eloquent->symbol,
            side: $eloquent->side,
            price: Price::fromCent($eloquent->price),
            amount: Amount::fromSubunit($eloquent->amount),
            status: $eloquent->status,
            createdAt: $eloquent->created_at
                ? \DateTimeImmutable::createFromInterface($eloquent->created_at)
                : null,
        );
    }

    private function applyToEloquent(DomainOrder $order, EloquentOrder $eloquent): void
    {
        $eloquent->user_id = $order->userId();
        $eloquent->symbol = $order->symbol();
        $eloquent->side = $order->side();
        $eloquent->price = $order->price()->cent();
        $eloquent->amount = $order->amount()->subunit();
        $eloquent->status = $order->status();
    }
}
