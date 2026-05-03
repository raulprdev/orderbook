<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Entities\Trade as DomainTrade;
use App\Domain\ValueObjects\Amount;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\Price;
use App\Models\Trade as EloquentTrade;
use App\Repositories\Contracts\TradeRepository;

final class EloquentTradeRepository implements TradeRepository
{
    public function save(DomainTrade $trade): DomainTrade
    {
        $eloquent = new EloquentTrade();

        $eloquent->buy_order_id = $trade->buyOrderId();
        $eloquent->sell_order_id = $trade->sellOrderId();
        $eloquent->buyer_user_id = $trade->buyerUserId();
        $eloquent->seller_user_id = $trade->sellerUserId();
        $eloquent->symbol = $trade->symbol();
        $eloquent->price = $trade->price()->cent();
        $eloquent->amount = $trade->amount()->subunit();
        $eloquent->volume = $trade->volume()->microUsd();
        $eloquent->fee = $trade->fee()->microUsd();

        $eloquent->save();

        // Round-trip via toDomain so any DB-side defaults, casts, or triggers
        // surface in the returned entity rather than relying on caller's input.
        return $this->toDomain($eloquent);
    }

    private function toDomain(EloquentTrade $eloquent): DomainTrade
    {
        return new DomainTrade(
            id: $eloquent->id,
            buyOrderId: $eloquent->buy_order_id,
            sellOrderId: $eloquent->sell_order_id,
            buyerUserId: $eloquent->buyer_user_id,
            sellerUserId: $eloquent->seller_user_id,
            symbol: $eloquent->symbol,
            price: Price::fromCent($eloquent->price),
            amount: Amount::fromSubunit($eloquent->amount),
            volume: Money::fromMicroUsd($eloquent->volume),
            fee: Money::fromMicroUsd($eloquent->fee),
        );
    }
}