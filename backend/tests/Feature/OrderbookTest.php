<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Symbol;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class OrderbookTest extends TestCase
{
    use RefreshDatabase;

    public function test_orderbook_requires_authentication(): void
    {
        $this->getJson(route('orders.index', ['symbol' => 'BTC']))->assertStatus(401);
    }

    public function test_orderbook_rejects_missing_symbol(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson(route('orders.index'))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['symbol']);
    }

    public function test_orderbook_returns_open_orders_for_symbol(): void
    {
        $user = User::factory()->create();

        Order::factory()->for($user)->buy()->forSymbol(Symbol::BTC)->create();
        Order::factory()->for($user)->sell()->forSymbol(Symbol::BTC)->create();
        Order::factory()->for($user)->buy()->forSymbol(Symbol::BTC)->filled()->create();
        Order::factory()->for($user)->buy()->forSymbol(Symbol::ETH)->create();

        $response = $this->actingAs($user)->getJson(route('orders.index', ['symbol' => 'BTC']));

        $response->assertOk();
        $response->assertJsonCount(2, 'orders');
    }
}
