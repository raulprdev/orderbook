<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\OrderStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OrderStatusTest extends TestCase
{
    public static function allowedTransitions(): array
    {
        return [
            'open to filled' => [OrderStatus::Open, OrderStatus::Filled],
            'open to cancelled' => [OrderStatus::Open, OrderStatus::Cancelled],
        ];
    }

    public static function rejectedTransitions(): array
    {
        return [
            'open to open' => [OrderStatus::Open,      OrderStatus::Open],
            'filled to open' => [OrderStatus::Filled,    OrderStatus::Open],
            'filled to filled' => [OrderStatus::Filled,    OrderStatus::Filled],
            'filled to cancelled' => [OrderStatus::Filled,    OrderStatus::Cancelled],
            'cancelled to open' => [OrderStatus::Cancelled, OrderStatus::Open],
            'cancelled to filled' => [OrderStatus::Cancelled, OrderStatus::Filled],
            'cancelled to cancelled' => [OrderStatus::Cancelled, OrderStatus::Cancelled],
        ];
    }

    #[DataProvider('allowedTransitions')]
    public function test_allowed_transitions_are_permitted(OrderStatus $from, OrderStatus $to): void
    {
        $this->assertTrue($from->canTransitionTo($to));
    }

    #[DataProvider('rejectedTransitions')]
    public function test_rejected_transitions_are_blocked(OrderStatus $from, OrderStatus $to): void
    {
        $this->assertFalse($from->canTransitionTo($to));
    }
}
