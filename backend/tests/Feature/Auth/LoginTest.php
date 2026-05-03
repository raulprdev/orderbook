<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_user_with_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'alice@example.com',
            'password' => 'secret-password',
        ]);

        $this->postJson(route('login'), [
            'email' => 'alice@example.com',
            'password' => 'secret-password',
        ])
            ->assertOk()
            ->assertJsonPath('user.email', 'alice@example.com');

        $this->assertAuthenticated();
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'alice@example.com',
            'password' => 'secret-password',
        ]);

        $this->postJson(route('login'), [
            'email' => 'alice@example.com',
            'password' => 'wrong-password',
        ])
            ->assertStatus(401);

        $this->assertGuest();
    }

    public function test_login_rejects_missing_fields(): void
    {
        $this->postJson(route('login'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }
}