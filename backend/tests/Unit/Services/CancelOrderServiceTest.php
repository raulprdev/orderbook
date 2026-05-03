<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Domain\Entities\Asset;
use App\Domain\Entities\Order;
use App\Domain\Entities\User;
use App\Domain\Exceptions\CannotCancelOrder;
use App\Domain\ValueObjects\Amount;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\Price;
use App\Enums\OrderStatus;
use App\Enums\Side;
use App\Enums\Symbol;
use App\Repositories\Contracts\AssetRepository;
use App\Repositories\Contracts\OrderRepository;
use App\Repositories\Contracts\UserRepository;
use App\Services\CancelOrderService;
use Closure;
use Illuminate\Database\ConnectionInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class CancelOrderServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const COMMISSION_BASIS_POINTS = 150;

    private const USER_ID = 100;

    private const ORDER_ID = 42;

    public function test_cancel_buy_refunds_locked_balance(): void
    {
        $user = $this->makeUser(balanceUsd: '0');
        $order = $this->makeOrder(side: Side::Buy);

        $orders = Mockery::mock(OrderRepository::class);
        $orders->expects('findOpenForUpdate')->with(self::ORDER_ID)->andReturn($order);
        $orders->expects('save')->with($order)->andReturn($order);

        $users = Mockery::mock(UserRepository::class);
        $users->expects('findByIdForUpdate')->with(self::USER_ID)->andReturn($user);
        $users->expects('save')->with($user)->andReturn($user);

        $service = new CancelOrderService(
            $orders,
            $users,
            Mockery::mock(AssetRepository::class),
            $this->mockedConnectionRunningClosure(),
            self::COMMISSION_BASIS_POINTS,
        );

        $service(orderId: self::ORDER_ID, userId: self::USER_ID);

        // Lock = $95000 * 0.01 * 1.015 = $964.25 = 964_250_000 micro
        $this->assertSame(964_250_000, $user->balance()->microUsd());
    }

    public function test_cancel_sell_releases_locked_asset(): void
    {
        $order = $this->makeOrder(side: Side::Sell);
        $asset = new Asset(
            id: 1,
            userId: self::USER_ID,
            symbol: Symbol::BTC,
            amount: Amount::fromDecimal('0.99'),
            lockedAmount: Amount::fromDecimal('0.01'),
        );

        $orders = Mockery::mock(OrderRepository::class);
        $orders->expects('findOpenForUpdate')->with(self::ORDER_ID)->andReturn($order);
        $orders->expects('save')->with($order)->andReturn($order);

        $assets = Mockery::mock(AssetRepository::class);
        $assets->expects('findByUserAndSymbolForUpdate')
            ->with(self::USER_ID, Symbol::BTC)
            ->andReturn($asset);
        $assets->expects('save')->with($asset)->andReturn($asset);

        $service = new CancelOrderService(
            $orders,
            Mockery::mock(UserRepository::class),
            $assets,
            $this->mockedConnectionRunningClosure(),
            self::COMMISSION_BASIS_POINTS,
        );

        $service(orderId: self::ORDER_ID, userId: self::USER_ID);

        $this->assertSame(100_000_000, $asset->amount()->subunit(), '1.0 BTC available after release');
        $this->assertSame(0, $asset->lockedAmount()->subunit(), 'no BTC locked after release');
    }

    public function test_cancel_transitions_order_to_cancelled(): void
    {
        $order = $this->makeOrder(side: Side::Buy);

        $orders = Mockery::mock(OrderRepository::class);
        $orders->expects('findOpenForUpdate')->andReturn($order);
        $orders->expects('save')->andReturn($order);

        $users = Mockery::mock(UserRepository::class);
        $users->expects('findByIdForUpdate')->andReturn($this->makeUser(balanceUsd: '0'));
        $users->expects('save')->andReturnUsing(fn (User $u) => $u);

        $service = new CancelOrderService(
            $orders,
            $users,
            Mockery::mock(AssetRepository::class),
            $this->mockedConnectionRunningClosure(),
            self::COMMISSION_BASIS_POINTS,
        );

        $service(orderId: self::ORDER_ID, userId: self::USER_ID);

        $this->assertSame(OrderStatus::Cancelled, $order->status());
    }

    public function test_cancel_throws_when_order_not_found(): void
    {
        $orders = Mockery::mock(OrderRepository::class);
        $orders->expects('findOpenForUpdate')->with(self::ORDER_ID)->andReturnNull();

        $service = new CancelOrderService(
            $orders,
            Mockery::mock(UserRepository::class),
            Mockery::mock(AssetRepository::class),
            $this->mockedConnectionRunningClosure(),
            self::COMMISSION_BASIS_POINTS,
        );

        $this->expectException(CannotCancelOrder::class);
        $service(orderId: self::ORDER_ID, userId: self::USER_ID);
    }

    public function test_cancel_throws_when_order_belongs_to_another_user(): void
    {
        $someoneElsesOrder = $this->makeOrder(side: Side::Buy, userId: 999);

        $orders = Mockery::mock(OrderRepository::class);
        $orders->expects('findOpenForUpdate')->with(self::ORDER_ID)->andReturn($someoneElsesOrder);

        $service = new CancelOrderService(
            $orders,
            Mockery::mock(UserRepository::class),
            Mockery::mock(AssetRepository::class),
            $this->mockedConnectionRunningClosure(),
            self::COMMISSION_BASIS_POINTS,
        );

        $this->expectException(CannotCancelOrder::class);
        $service(orderId: self::ORDER_ID, userId: self::USER_ID);
    }

    private function makeUser(string $balanceUsd): User
    {
        return new User(id: self::USER_ID, balance: Money::fromUsd($balanceUsd));
    }

    private function makeOrder(Side $side, int $userId = self::USER_ID): Order
    {
        return new Order(
            id: self::ORDER_ID,
            userId: $userId,
            symbol: Symbol::BTC,
            side: $side,
            price: Price::fromUsd('95000'),
            amount: Amount::fromDecimal('0.01'),
            status: OrderStatus::Open,
        );
    }

    private function mockedConnectionRunningClosure(): ConnectionInterface
    {
        $db = Mockery::mock(ConnectionInterface::class);
        $db->expects('transaction')->andReturnUsing(fn (Closure $callback) => $callback());

        return $db;
    }
}
