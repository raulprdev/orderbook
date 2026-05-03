<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DataTransferObjects\PlaceOrderData;
use App\Domain\Entities\Asset;
use App\Domain\Entities\Order;
use App\Domain\Entities\User;
use App\Domain\Exceptions\InsufficientAsset;
use App\Domain\Exceptions\InsufficientBalance;
use App\Domain\Matching\MatchingStrategy;
use App\Domain\ValueObjects\Amount;
use App\Domain\ValueObjects\Money;
use App\Enums\OrderStatus;
use App\Enums\Side;
use App\Enums\Symbol;
use App\Repositories\Contracts\AssetRepository;
use App\Repositories\Contracts\OrderRepository;
use App\Repositories\Contracts\UserRepository;
use App\Services\MatchOrderService;
use App\Services\PlaceOrderService;
use Closure;
use Illuminate\Database\ConnectionInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class PlaceOrderServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const COMMISSION_BASIS_POINTS = 150;

    private const USER_ID = 100;

    public function test_place_buy_with_no_match_persists_open_order_and_locks_balance(): void
    {
        $user = $this->makeUser(balanceUsd: '1000.00');

        $users = Mockery::mock(UserRepository::class);
        $users->expects('findByIdForUpdate')->with(self::USER_ID)->andReturn($user);
        $users->expects('save')->with($user)->andReturn($user);

        $orders = Mockery::mock(OrderRepository::class);
        $orders->expects('save')->andReturnUsing(
            fn (Order $o) => $this->orderWithId($o, id: 42)
        );

        $matcher = Mockery::mock(MatchingStrategy::class);
        $matcher->expects('findMatch')->andReturnNull();

        $service = new PlaceOrderService(
            $users,
            Mockery::mock(AssetRepository::class),
            $orders,
            $matcher,
            Mockery::mock(MatchOrderService::class),
            $this->mockedConnectionRunningClosure(),
            self::COMMISSION_BASIS_POINTS,
        );

        $order = $service(
            data: new PlaceOrderData(symbol: 'BTC', side: 'buy', price: '95000', amount: '0.01'),
            userId: self::USER_ID,
        );

        $this->assertSame(42, $order->id());
        $this->assertSame(OrderStatus::Open, $order->status());
        $this->assertSame(35_750_000, $user->balance()->microUsd(), '$1000 - $964.25 = $35.75');
    }

    public function test_place_sell_with_no_match_persists_open_order_and_locks_asset(): void
    {
        $user = $this->makeUser(balanceUsd: '0');
        $asset = new Asset(
            id: 1,
            userId: self::USER_ID,
            symbol: Symbol::BTC,
            amount: Amount::fromDecimal('1.0'),
            lockedAmount: Amount::fromDecimal('0'),
        );

        $users = Mockery::mock(UserRepository::class);
        $users->expects('findByIdForUpdate')->with(self::USER_ID)->andReturn($user);

        $assets = Mockery::mock(AssetRepository::class);
        $assets->expects('findByUserAndSymbolForUpdate')->with(self::USER_ID, Symbol::BTC)->andReturn($asset);
        $assets->expects('save')->with($asset)->andReturn($asset);

        $orders = Mockery::mock(OrderRepository::class);
        $orders->expects('save')->andReturnUsing(
            fn (Order $o) => $this->orderWithId($o, id: 42)
        );

        $matcher = Mockery::mock(MatchingStrategy::class);
        $matcher->expects('findMatch')->andReturnNull();

        $service = new PlaceOrderService(
            $users,
            $assets,
            $orders,
            $matcher,
            Mockery::mock(MatchOrderService::class),
            $this->mockedConnectionRunningClosure(),
            self::COMMISSION_BASIS_POINTS,
        );

        $order = $service(
            data: new PlaceOrderData(symbol: 'BTC', side: 'sell', price: '95000', amount: '0.01'),
            userId: self::USER_ID,
        );

        $this->assertSame(42, $order->id());
        $this->assertSame(99_000_000, $asset->amount()->subunit(), '0.99 BTC available');
        $this->assertSame(1_000_000, $asset->lockedAmount()->subunit(), '0.01 BTC locked');
    }

    public function test_place_buy_with_match_invokes_match_service(): void
    {
        $user = $this->makeUser(balanceUsd: '1000.00');

        $users = Mockery::mock(UserRepository::class);
        $users->expects('findByIdForUpdate')->andReturn($user);
        $users->expects('save')->andReturn($user);

        $orders = Mockery::mock(OrderRepository::class);
        $orders->expects('save')->andReturnUsing(
            fn (Order $o) => $this->orderWithId($o, id: 42)
        );

        $counter = $this->makeOrder(id: 99, userId: 200, side: Side::Sell);
        $matcher = Mockery::mock(MatchingStrategy::class);
        $matcher->expects('findMatch')->andReturn($counter);

        $matchService = Mockery::mock(MatchOrderService::class);
        $matchService->expects('settle')
            ->with(Mockery::on(fn (Order $o) => $o->id() === 42), $counter)
            ->andReturnUsing(function (Order $incoming, Order $counter) {
                $incoming->fill();

                return new \App\Domain\Events\OrderMatched(
                    buyOrderId: $incoming->id(),
                    sellOrderId: $counter->id(),
                    buyerUserId: $incoming->userId(),
                    sellerUserId: $counter->userId(),
                    symbol: $incoming->symbol(),
                    matchPrice: $counter->price(),
                    matchAmount: $incoming->amount(),
                    volume: \App\Domain\ValueObjects\Money::fromMicroUsd(0),
                    fee: \App\Domain\ValueObjects\Money::fromMicroUsd(0),
                );
            });

        $service = new PlaceOrderService(
            $users,
            Mockery::mock(AssetRepository::class),
            $orders,
            $matcher,
            $matchService,
            $this->mockedConnectionRunningClosure(),
            self::COMMISSION_BASIS_POINTS,
        );

        $order = $service(
            data: new PlaceOrderData(symbol: 'BTC', side: 'buy', price: '95000', amount: '0.01'),
            userId: self::USER_ID,
        );

        $this->assertSame(OrderStatus::Filled, $order->status());
    }

    public function test_place_buy_throws_when_insufficient_balance(): void
    {
        $user = $this->makeUser(balanceUsd: '50.00');

        $users = Mockery::mock(UserRepository::class);
        $users->expects('findByIdForUpdate')->andReturn($user);

        $service = new PlaceOrderService(
            $users,
            Mockery::mock(AssetRepository::class),
            Mockery::mock(OrderRepository::class),
            Mockery::mock(MatchingStrategy::class),
            Mockery::mock(MatchOrderService::class),
            $this->mockedConnectionRunningClosure(),
            self::COMMISSION_BASIS_POINTS,
        );

        $this->expectException(InsufficientBalance::class);
        $service(
            data: new PlaceOrderData(symbol: 'BTC', side: 'buy', price: '95000', amount: '0.01'),
            userId: self::USER_ID,
        );
    }

    public function test_place_sell_throws_when_user_has_no_asset_row(): void
    {
        $user = $this->makeUser(balanceUsd: '0');

        $users = Mockery::mock(UserRepository::class);
        $users->expects('findByIdForUpdate')->andReturn($user);

        $assets = Mockery::mock(AssetRepository::class);
        $assets->expects('findByUserAndSymbolForUpdate')->andReturnNull();

        $service = new PlaceOrderService(
            $users,
            $assets,
            Mockery::mock(OrderRepository::class),
            Mockery::mock(MatchingStrategy::class),
            Mockery::mock(MatchOrderService::class),
            $this->mockedConnectionRunningClosure(),
            self::COMMISSION_BASIS_POINTS,
        );

        $this->expectException(InsufficientAsset::class);
        $service(
            data: new PlaceOrderData(symbol: 'BTC', side: 'sell', price: '95000', amount: '0.01'),
            userId: self::USER_ID,
        );
    }

    private function makeUser(string $balanceUsd): User
    {
        return new User(id: self::USER_ID, balance: Money::fromUsd($balanceUsd));
    }

    private function makeOrder(int $id, int $userId, Side $side): Order
    {
        return new Order(
            id: $id,
            userId: $userId,
            symbol: Symbol::BTC,
            side: $side,
            price: \App\Domain\ValueObjects\Price::fromUsd('95000'),
            amount: Amount::fromDecimal('0.01'),
            status: OrderStatus::Open,
        );
    }

    private function orderWithId(Order $order, int $id): Order
    {
        return new Order(
            id: $id,
            userId: $order->userId(),
            symbol: $order->symbol(),
            side: $order->side(),
            price: $order->price(),
            amount: $order->amount(),
            status: $order->status(),
        );
    }

    private function mockedConnectionRunningClosure(): ConnectionInterface
    {
        $db = Mockery::mock(ConnectionInterface::class);
        $db->expects('transaction')->andReturnUsing(fn (Closure $callback) => $callback());

        return $db;
    }
}