<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\Side;
use App\Enums\Symbol;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
final class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'symbol' => Symbol::BTC,
            'side' => Side::Buy,
            'price' => 9_500_000,
            'amount' => 1_000_000,
            'status' => OrderStatus::Open,
        ];
    }

    public function buy(): self
    {
        return $this->state(['side' => Side::Buy]);
    }

    public function sell(): self
    {
        return $this->state(['side' => Side::Sell]);
    }

    public function filled(): self
    {
        return $this->state(['status' => OrderStatus::Filled]);
    }

    public function cancelled(): self
    {
        return $this->state(['status' => OrderStatus::Cancelled]);
    }

    public function forSymbol(Symbol $symbol): self
    {
        return $this->state(['symbol' => $symbol]);
    }

    public function atPriceCent(int $cent): self
    {
        return $this->state(['price' => $cent]);
    }

    public function forAmountSubunit(int $subunit): self
    {
        return $this->state(['amount' => $subunit]);
    }
}
