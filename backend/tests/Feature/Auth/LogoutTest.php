<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_logout_succeeds_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('logout'))
            ->assertOk()
            ->assertJsonPath('message', 'Logged out');
    }

    public function test_logout_rejects_unauthenticated_request(): void
    {
        $this->postJson(route('logout'))->assertStatus(401);
    }
}
