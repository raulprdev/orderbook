<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Domain\Entities\Asset;
use App\Domain\Entities\Order;
use App\Domain\Entities\Trade;
use App\Domain\Entities\User;
use App\Domain\Events\OrderMatched;
use App\Domain\Exceptions\InvalidOrderPair;
use App\Domain\ValueObjects\Amount;
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\Price;
use App\Enums\OrderStatus;
use App\Enums\Side;
use App\Enums\Symbol;
use App\Repositories\Contracts\AssetRepository;
use App\Repositories\Contracts\OrderRepository;
use App\Repositories\Contracts\TradeRepository;
use App\Repositories\Contracts\UserRepository;
use App\Services\MatchOrderService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class MatchOrderServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const COMMISSION_BASIS_POINTS = 150;

    private const BUYER_ID = 100;

    private const SELLER_ID = 200;

    public function test_settle_at_match_price_credits_seller_and_buyer(): void
    {
        $buyer = $this->makeUser(self::BUYER_ID, balanceUsd: '0');
        $seller = $this->makeUser(self::SELLER_ID, balanceUsd: '0');
        $buyerAsset = $this->makeAsset(self::BUYER_ID, available: '0', locked: '0');
        $sellerAsset = $this->makeAsset(self::SELLER_ID, available: '0', locked: '0.01');

        $buyOrder = $this->makeOrder(id: 1, userId: self::BUYER_ID, side: Side::Buy, priceUsd: '95000');
        $sellOrder = $this->makeOrder(id: 2, userId: self::SELLER_ID, side: Side::Sell, priceUsd: '95000');

        [$users, $assets, $orders, $trades] = $this->setupRepositoriesForSettle(
            buyer: $buyer,
            seller: $seller,
            buyerAsset: $buyerAsset,
            sellerAsset: $sellerAsset,
        );

        $service = new MatchOrderService($users, $assets, $orders, $trades, self::COMMISSION_BASIS_POINTS);

        $service->settle(incoming: $buyOrder, counter: $sellOrder);

        $this->assertSame(0, $buyer->balance()->microUsd(), 'buyer locked exactly the cost; no refund');
        $this->assertSame(950_000_000, $seller->balance()->microUsd(), 'seller credited full volume $950');
        $this->assertSame(1_000_000, $buyerAsset->amount()->subunit(), 'buyer credited 0.01 BTC');
        $this->assertSame(0, $sellerAsset->lockedAmount()->subunit(), 'seller locked asset removed');
    }

    public function test_settle_at_lower_match_price_refunds_buyer_overlock(): void
    {
        $buyer = $this->makeUser(self::BUYER_ID, balanceUsd: '0');
        $seller = $this->makeUser(self::SELLER_ID, balanceUsd: '0');
        $buyerAsset = $this->makeAsset(self::BUYER_ID, available: '0', locked: '0');
        $sellerAsset = $this->makeAsset(self::SELLER_ID, available: '0', locked: '0.01');

        $buyOrder = $this->makeOrder(id: 1, userId: self::BUYER_ID, side: Side::Buy, priceUsd: '95000');
        $sellOrder = $this->makeOrder(id: 2, userId: self::SELLER_ID, side: Side::Sell, priceUsd: '94000');

        [$users, $assets, $orders, $trades] = $this->setupRepositoriesForSettle(
            buyer: $buyer,
            seller: $seller,
            buyerAsset: $buyerAsset,
            sellerAsset: $sellerAsset,
        );

        $service = new MatchOrderService($users, $assets, $orders, $trades, self::COMMISSION_BASIS_POINTS);

        $service->settle(incoming: $buyOrder, counter: $sellOrder);

        // Locked = $95000 * 0.01 * 1.015 = $964.25 = 964_250_000 micro
        // Cost   = $94000 * 0.01 * 1.015 = $954.10 = 954_100_000 micro
        // Refund = $10.15 = 10_150_000 micro
        $this->assertSame(10_150_000, $buyer->balance()->microUsd(), 'buyer refunded the over-lock');
        $this->assertSame(940_000_000, $seller->balance()->microUsd(), 'seller credited match volume $940');
    }

    public function test_settle_transitions_both_orders_to_filled(): void
    {
        $buyOrder = $this->makeOrder(id: 1, userId: self::BUYER_ID, side: Side::Buy, priceUsd: '95000');
        $sellOrder = $this->makeOrder(id: 2, userId: self::SELLER_ID, side: Side::Sell, priceUsd: '95000');

        [$users, $assets, $orders, $trades] = $this->setupRepositoriesForSettle(
            buyer: $this->makeUser(self::BUYER_ID, balanceUsd: '0'),
            seller: $this->makeUser(self::SELLER_ID, balanceUsd: '0'),
            buyerAsset: $this->makeAsset(self::BUYER_ID, available: '0', locked: '0'),
            sellerAsset: $this->makeAsset(self::SELLER_ID, available: '0', locked: '0.01'),
        );

        $service = new MatchOrderService($users, $assets, $orders, $trades, self::COMMISSION_BASIS_POINTS);

        $service->settle(incoming: $buyOrder, counter: $sellOrder);

        $this->assertSame(OrderStatus::Filled, $buyOrder->status());
        $this->assertSame(OrderStatus::Filled, $sellOrder->status());
    }

    public function test_settle_persists_trade_with_match_values(): void
    {
        $buyOrder = $this->makeOrder(id: 1, userId: self::BUYER_ID, side: Side::Buy, priceUsd: '95000');
        $sellOrder = $this->makeOrder(id: 2, userId: self::SELLER_ID, side: Side::Sell, priceUsd: '94000');

        [$users, $assets, $orders, $trades] = $this->setupRepositoriesForSettle(
            buyer: $this->makeUser(self::BUYER_ID, balanceUsd: '0'),
            seller: $this->makeUser(self::SELLER_ID, balanceUsd: '0'),
            buyerAsset: $this->makeAsset(self::BUYER_ID, available: '0', locked: '0'),
            sellerAsset: $this->makeAsset(self::SELLER_ID, available: '0', locked: '0.01'),
            tradeAssertion: function (Trade $trade) {
                $this->assertSame(1, $trade->buyOrderId());
                $this->assertSame(2, $trade->sellOrderId());
                $this->assertSame(self::BUYER_ID, $trade->buyerUserId());
                $this->assertSame(self::SELLER_ID, $trade->sellerUserId());
                $this->assertSame(9_400_000, $trade->price()->cent(), 'trade recorded at maker price');
                $this->assertSame(1_000_000, $trade->amount()->subunit());
                $this->assertSame(940_000_000, $trade->volume()->microUsd());
                $this->assertSame(14_100_000, $trade->fee()->microUsd());
            },
        );

        $service = new MatchOrderService($users, $assets, $orders, $trades, self::COMMISSION_BASIS_POINTS);
        $event = $service->settle(incoming: $buyOrder, counter: $sellOrder);

        $this->assertInstanceOf(OrderMatched::class, $event);
        $this->assertSame(1, $event->buyOrderId);
        $this->assertSame(9_400_000, $event->matchPrice->cent());
    }

    public function test_settle_throws_when_both_orders_are_same_side(): void
    {
        $buyA = $this->makeOrder(id: 1, userId: self::BUYER_ID, side: Side::Buy, priceUsd: '95000');
        $buyB = $this->makeOrder(id: 2, userId: self::SELLER_ID, side: Side::Buy, priceUsd: '95000');

        $service = $this->makeServiceWithStrictMocks();

        $this->expectException(InvalidOrderPair::class);
        $service->settle(incoming: $buyA, counter: $buyB);
    }

    public function test_settle_throws_when_orders_have_different_symbols(): void
    {
        $buy = $this->makeOrder(id: 1, userId: self::BUYER_ID, side: Side::Buy, priceUsd: '95000');
        $sell = new Order(
            id: 2,
            userId: self::SELLER_ID,
            symbol: Symbol::ETH,
            side: Side::Sell,
            price: Price::fromUsd('95000'),
            amount: Amount::fromDecimal('0.01'),
            status: OrderStatus::Open,
        );

        $service = $this->makeServiceWithStrictMocks();

        $this->expectException(InvalidOrderPair::class);
        $service->settle(incoming: $buy, counter: $sell);
    }

    public function test_settle_throws_when_orders_have_different_amounts(): void
    {
        $buy = $this->makeOrder(id: 1, userId: self::BUYER_ID, side: Side::Buy, priceUsd: '95000');
        $sell = new Order(
            id: 2,
            userId: self::SELLER_ID,
            symbol: Symbol::BTC,
            side: Side::Sell,
            price: Price::fromUsd('95000'),
            amount: Amount::fromDecimal('0.02'),
            status: OrderStatus::Open,
        );

        $service = $this->makeServiceWithStrictMocks();

        $this->expectException(InvalidOrderPair::class);
        $service->settle(incoming: $buy, counter: $sell);
    }

    public function test_settle_throws_when_either_order_is_not_open(): void
    {
        $buy = $this->makeOrder(id: 1, userId: self::BUYER_ID, side: Side::Buy, priceUsd: '95000');
        $sell = new Order(
            id: 2,
            userId: self::SELLER_ID,
            symbol: Symbol::BTC,
            side: Side::Sell,
            price: Price::fromUsd('95000'),
            amount: Amount::fromDecimal('0.01'),
            status: OrderStatus::Filled,
        );

        $service = $this->makeServiceWithStrictMocks();

        $this->expectException(InvalidOrderPair::class);
        $service->settle(incoming: $buy, counter: $sell);
    }

    private function makeServiceWithStrictMocks(): MatchOrderService
    {
        return new MatchOrderService(
            Mockery::mock(UserRepository::class),
            Mockery::mock(AssetRepository::class),
            Mockery::mock(OrderRepository::class),
            Mockery::mock(TradeRepository::class),
            self::COMMISSION_BASIS_POINTS,
        );
    }

    private function makeUser(int $id, string $balanceUsd): User
    {
        return new User(id: $id, balance: Money::fromUsd($balanceUsd));
    }

    private function makeAsset(int $userId, string $available, string $locked): Asset
    {
        return new Asset(
            id: $userId,
            userId: $userId,
            symbol: Symbol::BTC,
            amount: Amount::fromDecimal($available),
            lockedAmount: Amount::fromDecimal($locked),
        );
    }

    private function makeOrder(int $id, int $userId, Side $side, string $priceUsd): Order
    {
        return new Order(
            id: $id,
            userId: $userId,
            symbol: Symbol::BTC,
            side: $side,
            price: Price::fromUsd($priceUsd),
            amount: Amount::fromDecimal('0.01'),
            status: OrderStatus::Open,
        );
    }

    /**
     * @return array{0: UserRepository, 1: AssetRepository, 2: OrderRepository, 3: TradeRepository}
     */
    private function setupRepositoriesForSettle(
        User $buyer,
        User $seller,
        Asset $buyerAsset,
        Asset $sellerAsset,
        ?\Closure $tradeAssertion = null,
    ): array {
        $users = Mockery::mock(UserRepository::class);
        $users->expects('findByIdForUpdate')->with(self::BUYER_ID)->andReturn($buyer);
        $users->expects('findByIdForUpdate')->with(self::SELLER_ID)->andReturn($seller);
        $users->expects('save')->with($buyer)->andReturn($buyer);
        $users->expects('save')->with($seller)->andReturn($seller);

        $assets = Mockery::mock(AssetRepository::class);
        $assets->expects('findOrCreateForUpdate')->with(self::BUYER_ID, Symbol::BTC)->andReturn($buyerAsset);
        $assets->expects('findByUserAndSymbolForUpdate')->with(self::SELLER_ID, Symbol::BTC)->andReturn($sellerAsset);
        $assets->expects('save')->with($buyerAsset)->andReturn($buyerAsset);
        $assets->expects('save')->with($sellerAsset)->andReturn($sellerAsset);

        $orders = Mockery::mock(OrderRepository::class);
        $orders->expects('save')->twice()->andReturnUsing(fn (Order $o) => $o);

        $trades = Mockery::mock(TradeRepository::class);
        $trades->expects('save')->andReturnUsing(function (Trade $trade) use ($tradeAssertion) {
            if ($tradeAssertion !== null) {
                ($tradeAssertion)($trade);
            }

            return $trade;
        });

        return [$users, $assets, $orders, $trades];
    }
}