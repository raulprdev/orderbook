<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\Order;
use App\Domain\Exceptions\InvalidOrder;
use App\Domain\Exceptions\InvalidOrderTransition;
use App\Domain\ValueObjects\Amount;
use App\Domain\ValueObjects\Price;
use App\Enums\OrderStatus;
use App\Enums\Side;
use App\Enums\Symbol;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function test_constructor_throws_when_amount_is_zero(): void
    {
        $this->expectException(InvalidOrder::class);
        $this->makeOrder(amount: Amount::fromSubunit(0));
    }

    public function test_fill_transitions_open_order_to_filled(): void
    {
        $order = $this->makeOrder();

        $order->fill();

        $this->assertSame(OrderStatus::Filled, $order->status());
    }

    public function test_cancel_transitions_open_order_to_cancelled(): void
    {
        $order = $this->makeOrder();

        $order->cancel();

        $this->assertSame(OrderStatus::Cancelled, $order->status());
    }

    public static function completedStatuses(): array
    {
        $cases = [];
        foreach (OrderStatus::cases() as $status) {
            if ($status->isCompleted()) {
                $cases[$status->value] = [$status];
            }
        }

        return $cases;
    }

    #[DataProvider('completedStatuses')]
    public function test_fill_throws_on_completed_order(OrderStatus $status): void
    {
        $order = $this->makeOrder(status: $status);

        $this->expectException(InvalidOrderTransition::class);
        $order->fill();
    }

    #[DataProvider('completedStatuses')]
    public function test_cancel_throws_on_completed_order(OrderStatus $status): void
    {
        $order = $this->makeOrder(status: $status);

        $this->expectException(InvalidOrderTransition::class);
        $order->cancel();
    }

    private function makeOrder(
        OrderStatus $status = OrderStatus::Open,
        ?Amount $amount = null,
    ): Order {
        return new Order(
            id: 1,
            userId: 100,
            symbol: Symbol::BTC,
            side: Side::Buy,
            price: Price::fromUsd('95000'),
            amount: $amount ?? Amount::fromDecimal('0.01'),
            status: $status,
        );
    }
}
