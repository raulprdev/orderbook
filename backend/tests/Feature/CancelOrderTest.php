<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class CancelOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancel_order_requires_authentication(): void
    {
        $this->postJson(route('orders.cancel', ['order' => 1]))->assertStatus(401);
    }

    public function test_cancel_order_transitions_open_buy_to_cancelled_and_refunds(): void
    {
        $user = User::factory()->create(['balance' => 0]);
        $order = Order::factory()
            ->for($user)
            ->buy()
            ->atPriceCent(9_500_000)
            ->forAmountSubunit(1_000_000)
            ->create();

        $response = $this->actingAs($user)
            ->postJson(route('orders.cancel', ['order' => $order->id]));

        $response->assertOk();
        $response->assertJsonPath('order.status', 'cancelled');
        $this->assertDatabaseHas(Order::class, [
            'id' => $order->id,
            'status' => OrderStatus::Cancelled->value,
        ]);
        $this->assertSame(964_250_000, $user->fresh()->balance);
    }

    public function test_cancel_order_rejects_someone_elses_order(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $order = Order::factory()->for($owner)->buy()->create();

        $this->actingAs($intruder)
            ->postJson(route('orders.cancel', ['order' => $order->id]))
            ->assertStatus(422);
    }

    public function test_cancel_order_rejects_already_filled_order(): void
    {
        $user = User::factory()->create();
        $order = Order::factory()->for($user)->buy()->filled()->create();

        $this->actingAs($user)
            ->postJson(route('orders.cancel', ['order' => $order->id]))
            ->assertStatus(422);
    }
}