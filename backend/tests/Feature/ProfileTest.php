<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Symbol;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_requires_authentication(): void
    {
        $this->getJson(route('profile'))->assertStatus(401);
    }

    public function test_profile_returns_user_balance_and_assets(): void
    {
        $user = User::factory()->create([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'balance' => 1_234_500_000,
        ]);

        Asset::factory()
            ->for($user)
            ->forSymbol(Symbol::BTC)
            ->withAmountSubunit(50_000_000)
            ->withLockedSubunit(1_000_000)
            ->create();

        Asset::factory()
            ->for($user)
            ->forSymbol(Symbol::ETH)
            ->withAmountSubunit(1_200_000_000)
            ->create();

        $response = $this->actingAs($user)->getJson(route('profile'));

        $response->assertOk();
        $response->assertJsonPath('user.id', $user->id);
        $response->assertJsonPath('user.name', 'Alice');
        $response->assertJsonPath('user.email', 'alice@example.com');
        $response->assertJsonPath('balance', '1234.50');
        $response->assertJsonCount(2, 'assets');
    }
}
