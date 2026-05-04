<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PlaceOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_place_order_requires_authentication(): void
    {
        $this->postJson(route('orders.store'), $this->validBuyPayload())
            ->assertStatus(401);
    }

    public function test_place_order_rejects_missing_fields(): void
    {
        $user = User::factory()->create(['balance' => 1_000_000_000]);

        $this->actingAs($user)
            ->postJson(route('orders.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['symbol', 'side', 'price', 'amount']);
    }

    public function test_place_order_rejects_unknown_symbol(): void
    {
        $user = User::factory()->create(['balance' => 1_000_000_000]);

        $this->actingAs($user)
            ->postJson(route('orders.store'), [
                'symbol' => 'DOGE',
                'side' => 'buy',
                'price' => '95000',
                'amount' => '0.01',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['symbol']);
    }

    public function test_place_buy_order_persists_open_order(): void
    {
        $user = User::factory()->create(['balance' => 1_000_000_000]);

        $response = $this->actingAs($user)
            ->postJson(route('orders.store'), $this->validBuyPayload());

        $response->assertCreated();
        $response->assertJsonPath('order.status', 'open');
        $response->assertJsonPath('order.side', 'buy');
        $this->assertDatabaseHas(Order::class, [
            'user_id' => $user->id,
            'symbol' => 'BTC',
            'side' => 'buy',
            'status' => 'open',
        ]);
    }

    public function test_place_buy_order_rejects_insufficient_balance(): void
    {
        $user = User::factory()->create(['balance' => 50_000_000]);

        $this->actingAs($user)
            ->postJson(route('orders.store'), $this->validBuyPayload())
            ->assertStatus(422);
    }

    private function validBuyPayload(): array
    {
        return [
            'symbol' => 'BTC',
            'side' => 'buy',
            'price' => '95000',
            'amount' => '0.01',
        ];
    }
}