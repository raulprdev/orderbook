<?php

declare(strict_types=1);

namespace App\Services;

use App\DataTransferObjects\PlaceOrderData;
use App\Domain\Entities\Order;
use App\Domain\Entities\User;
use App\Domain\Exceptions\InsufficientAsset;
use App\Domain\Matching\MatchingStrategy;
use App\Domain\ValueObjects\Amount;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\Price;
use App\Enums\OrderStatus;
use App\Enums\Side;
use App\Enums\Symbol;
use App\Repositories\Contracts\AssetRepository;
use App\Repositories\Contracts\OrderRepository;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Database\ConnectionInterface;

final class PlaceOrderService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly AssetRepository $assets,
        private readonly OrderRepository $orders,
        private readonly MatchingStrategy $matcher,
        private readonly MatchOrderService $matchService,
        private readonly ConnectionInterface $db,
        private readonly int $commissionBasisPoints,
    ) {}

    public function __invoke(PlaceOrderData $data, int $userId): Order
    {
        $symbol = Symbol::from($data->symbol);
        $side = Side::from($data->side);
        $price = Price::fromUsd($data->price);
        $amount = Amount::fromDecimal($data->amount);

        return $this->db->transaction(
            fn () => $this->place($userId, $symbol, $side, $price, $amount)
        );
    }

    private function place(int $userId, Symbol $symbol, Side $side, Price $price, Amount $amount): Order
    {
        $user = $this->users->findByIdForUpdate($userId);

        $side === Side::Buy
            ? $this->lockBuyerFunds($user, $price, $amount)
            : $this->lockSellerAsset($userId, $symbol, $amount);

        $order = $this->orders->save(new Order(
            id: null,
            userId: $userId,
            symbol: $symbol,
            side: $side,
            price: $price,
            amount: $amount,
            status: OrderStatus::Open,
        ));

        $counter = $this->matcher->findMatch($order);
        if ($counter !== null) {
            $this->matchService->settle($order, $counter);
        }

        return $order;
    }

    private function lockBuyerFunds(User $user, Price $price, Amount $amount): void
    {
        $volume = Money::fromVolume($price, $amount);
        $fee = $volume->applyBasisPoints($this->commissionBasisPoints);
        $totalLock = $volume->plus($fee);

        $user->debit($totalLock);
        $this->users->save($user);
    }

    private function lockSellerAsset(int $userId, Symbol $symbol, Amount $amount): void
    {
        $asset = $this->assets->findByUserAndSymbolForUpdate($userId, $symbol);
        if ($asset === null) {
            throw new InsufficientAsset(sprintf(
                'User %d has no %s asset',
                $userId,
                $symbol->value,
            ));
        }

        $asset->lock($amount);
        $this->assets->save($asset);
    }
}