<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MyOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_orders_requires_authentication(): void
    {
        $this->getJson(route('orders.mine'))->assertStatus(401);
    }

    public function test_my_orders_returns_only_authenticated_users_orders(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        Order::factory()->for($owner)->buy()->create();
        Order::factory()->for($owner)->sell()->filled()->create();
        Order::factory()->for($other)->buy()->create();

        $response = $this->actingAs($owner)->getJson(route('orders.mine'));

        $response->assertOk();
        $response->assertJsonCount(2, 'orders');
    }

    public function test_my_orders_returns_empty_array_when_user_has_no_orders(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('orders.mine'));

        $response->assertOk();
        $response->assertJsonPath('orders', []);
    }
}