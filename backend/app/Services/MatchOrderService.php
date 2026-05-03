<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Entities\Order;
use App\Domain\Entities\Trade;
use App\Domain\Events\OrderMatched;
use App\Domain\Exceptions\InvalidOrderPair;
use App\Domain\ValueObjects\Amount;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\Price;
use App\Enums\Side;
use App\Repositories\Contracts\AssetRepository;
use App\Repositories\Contracts\OrderRepository;
use App\Repositories\Contracts\TradeRepository;
use App\Repositories\Contracts\UserRepository;

class MatchOrderService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AssetRepository $assets,
        private readonly OrderRepository $orders,
        private readonly TradeRepository $trades,
        private readonly int $commissionBasisPoints,
    ) {}

    public function settle(Order $incoming, Order $counter): OrderMatched
    {
        $this->ensureValidPair($incoming, $counter);

        [$buy, $sell] = $this->identifyBuyAndSell($incoming, $counter);
        $matchPrice = $counter->price();
        $matchAmount = $incoming->amount();

        $volume = Money::fromVolume($matchPrice, $matchAmount);
        $fee = $volume->applyBasisPoints($this->commissionBasisPoints);

        $this->settleBuyer($buy, $matchAmount, $volume, $fee);
        $this->settleSeller($sell, $matchAmount, $volume);
        $this->completeOrders($incoming, $counter);
        $this->recordTrade($buy, $sell, $matchPrice, $matchAmount, $volume, $fee);

        return $this->composeEvent($buy, $sell, $matchPrice, $matchAmount, $volume, $fee);
    }

    private function ensureValidPair(Order $incoming, Order $counter): void
    {
        if ($incoming->side() === $counter->side()) {
            throw new InvalidOrderPair(sprintf(
                'settle requires opposite sides; got two %s orders',
                $incoming->side()->value,
            ));
        }

        if ($incoming->symbol() !== $counter->symbol()) {
            throw new InvalidOrderPair(sprintf(
                'settle requires same symbol; got %s and %s',
                $incoming->symbol()->value,
                $counter->symbol()->value,
            ));
        }

        if ($incoming->amount()->subunit() !== $counter->amount()->subunit()) {
            throw new InvalidOrderPair(sprintf(
                'settle requires equal amounts; got %d and %d subunits',
                $incoming->amount()->subunit(),
                $counter->amount()->subunit(),
            ));
        }

        if (! $incoming->status()->isOpen() || ! $counter->status()->isOpen()) {
            throw new InvalidOrderPair('settle requires both orders to be open');
        }
    }

    /**
     * @return array{0: Order, 1: Order}
     */
    private function identifyBuyAndSell(Order $incoming, Order $counter): array
    {
        return $incoming->side() === Side::Buy
            ? [$incoming, $counter]
            : [$counter, $incoming];
    }

    private function settleBuyer(Order $buy, Amount $matchAmount, Money $volume, Money $fee): void
    {
        $buyer = $this->users->findByIdForUpdate($buy->userId());
        $refund = $this->buyerOverlock($buy, $matchAmount, $volume, $fee);
        if ($refund->microUsd() > 0) {
            $buyer->credit($refund);
        }
        $this->users->save($buyer);

        $buyerAsset = $this->assets->findOrCreateForUpdate($buy->userId(), $buy->symbol());
        $buyerAsset->credit($matchAmount);
        $this->assets->save($buyerAsset);
    }

    private function settleSeller(Order $sell, Amount $matchAmount, Money $volume): void
    {
        $seller = $this->users->findByIdForUpdate($sell->userId());
        $seller->credit($volume);
        $this->users->save($seller);

        $sellerAsset = $this->assets->findByUserAndSymbolForUpdate($sell->userId(), $sell->symbol());
        $sellerAsset->debit($matchAmount);
        $this->assets->save($sellerAsset);
    }

    private function buyerOverlock(
        Order $buy,
        Amount $matchAmount,
        Money $matchVolume,
        Money $matchFee,
    ): Money {
        $lockedVolume = Money::fromVolume($buy->price(), $matchAmount);
        $lockedFee = $lockedVolume->applyBasisPoints($this->commissionBasisPoints);
        $totalLocked = $lockedVolume->plus($lockedFee);
        $totalUsed = $matchVolume->plus($matchFee);

        return $totalLocked->minus($totalUsed);
    }

    private function completeOrders(Order $incoming, Order $counter): void
    {
        $incoming->fill();
        $counter->fill();
        $this->orders->save($incoming);
        $this->orders->save($counter);
    }

    private function recordTrade(
        Order $buy,
        Order $sell,
        Price $matchPrice,
        Amount $matchAmount,
        Money $volume,
        Money $fee,
    ): void {
        $this->trades->save(new Trade(
            id: null,
            buyOrderId: $buy->id(),
            sellOrderId: $sell->id(),
            buyerUserId: $buy->userId(),
            sellerUserId: $sell->userId(),
            symbol: $buy->symbol(),
            price: $matchPrice,
            amount: $matchAmount,
            volume: $volume,
            fee: $fee,
        ));
    }

    private function composeEvent(
        Order $buy,
        Order $sell,
        Price $matchPrice,
        Amount $matchAmount,
        Money $volume,
        Money $fee,
    ): OrderMatched {
        return new OrderMatched(
            buyOrderId: $buy->id(),
            sellOrderId: $sell->id(),
            buyerUserId: $buy->userId(),
            sellerUserId: $sell->userId(),
            symbol: $buy->symbol(),
            matchPrice: $matchPrice,
            matchAmount: $matchAmount,
            volume: $volume,
            fee: $fee,
        );
    }
}
