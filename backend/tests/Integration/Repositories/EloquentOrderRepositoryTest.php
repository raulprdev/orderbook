<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Domain\Entities\Order as DomainOrder;
use App\Domain\ValueObjects\Amount;
use App\Domain\ValueObjects\Price;
use App\Enums\OrderStatus;
use App\Enums\Side;
use App\Enums\Symbol;
use App\Models\Order as EloquentOrder;
use App\Models\User;
use App\Repositories\EloquentOrderRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EloquentOrderRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentOrderRepository $repository;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentOrderRepository;
        $this->user = User::factory()->create();
    }

    public function test_save_persists_new_order_and_returns_with_assigned_id(): void
    {
        $order = $this->makeDomainOrder();

        $saved = $this->repository->save($order);

        $this->assertNotNull($saved->id());
        $this->assertSame($this->user->id, $saved->userId());
        $this->assertSame(Symbol::BTC, $saved->symbol());
        $this->assertSame(Side::Buy, $saved->side());
        $this->assertSame(9_500_000, $saved->price()->cent());
        $this->assertSame(1_000_000, $saved->amount()->subunit());
        $this->assertSame(OrderStatus::Open, $saved->status());
    }

    public function test_save_updates_existing_order_status(): void
    {
        $order = $this->repository->save($this->makeDomainOrder());

        $persisted = $this->repository->findById($order->id());
        $persisted->fill();
        $this->repository->save($persisted);

        $reread = $this->repository->findById($order->id());
        $this->assertSame(OrderStatus::Filled, $reread->status());
    }

    public function test_first_matchable_returns_open_sell_when_buy_arrives(): void
    {
        $sellOrder = $this->seedOrder(side: Side::Sell, priceCent: 9_400_000);
        $incomingBuy = $this->makeDomainOrder(side: Side::Buy, priceUsd: '95000');

        $match = $this->repository->firstMatchableCounterOrder($incomingBuy);

        $this->assertNotNull($match);
        $this->assertSame($sellOrder->id, $match->id());
    }

    public function test_first_matchable_returns_null_when_no_open_counter_order(): void
    {
        $this->seedOrder(side: Side::Sell, priceCent: 9_600_000);
        $incomingBuy = $this->makeDomainOrder(side: Side::Buy, priceUsd: '95000');

        $this->assertNull($this->repository->firstMatchableCounterOrder($incomingBuy));
    }

    public function test_first_matchable_skips_filled_orders(): void
    {
        $this->seedOrder(side: Side::Sell, priceCent: 9_400_000, status: OrderStatus::Filled);
        $incomingBuy = $this->makeDomainOrder(side: Side::Buy, priceUsd: '95000');

        $this->assertNull($this->repository->firstMatchableCounterOrder($incomingBuy));
    }

    public function test_first_matchable_returns_oldest_open_order_first(): void
    {
        $older = $this->seedOrder(side: Side::Sell, priceCent: 9_400_000, createdAt: '2026-05-01 10:00:00');
        $this->seedOrder(side: Side::Sell, priceCent: 9_400_000, createdAt: '2026-05-01 11:00:00');

        $match = $this->repository->firstMatchableCounterOrder(
            $this->makeDomainOrder(side: Side::Buy, priceUsd: '95000')
        );

        $this->assertSame($older->id, $match->id());
    }

    public function test_first_matchable_returns_null_for_different_amount(): void
    {
        $this->seedOrder(side: Side::Sell, priceCent: 9_400_000, amountSubunit: 2_000_000);
        $incomingBuy = $this->makeDomainOrder(side: Side::Buy, priceUsd: '95000', amountDecimal: '0.01');

        $this->assertNull($this->repository->firstMatchableCounterOrder($incomingBuy));
    }

    public function test_find_open_for_update_returns_open_order(): void
    {
        $eloquent = $this->seedOrder(side: Side::Buy, priceCent: 9_500_000);

        $found = $this->repository->findOpenForUpdate($eloquent->id);

        $this->assertNotNull($found);
        $this->assertSame($eloquent->id, $found->id());
    }

    public function test_find_open_for_update_returns_null_for_filled_order(): void
    {
        $eloquent = $this->seedOrder(side: Side::Buy, priceCent: 9_500_000, status: OrderStatus::Filled);

        $this->assertNull($this->repository->findOpenForUpdate($eloquent->id));
    }

    private function makeDomainOrder(
        Side $side = Side::Buy,
        string $priceUsd = '95000',
        string $amountDecimal = '0.01',
    ): DomainOrder {
        return new DomainOrder(
            id: null,
            userId: $this->user->id,
            symbol: Symbol::BTC,
            side: $side,
            price: Price::fromUsd($priceUsd),
            amount: Amount::fromDecimal($amountDecimal),
            status: OrderStatus::Open,
        );
    }

    private function seedOrder(
        Side $side,
        int $priceCent,
        OrderStatus $status = OrderStatus::Open,
        ?string $createdAt = null,
        int $amountSubunit = 1_000_000,
    ): EloquentOrder {
        return EloquentOrder::create([
            'user_id' => $this->user->id,
            'symbol' => Symbol::BTC,
            'side' => $side,
            'price' => $priceCent,
            'amount' => $amountSubunit,
            'status' => $status,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }
}
