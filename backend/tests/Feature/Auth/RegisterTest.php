<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_creates_user_logs_in_and_returns_user_payload(): void
    {
        $response = $this->postJson(route('register'), [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('user.name', 'Alice');
        $response->assertJsonPath('user.email', 'alice@example.com');
        $this->assertDatabaseHas(User::class, ['email' => 'alice@example.com']);
        $this->assertAuthenticated();
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson(route('register'), [
            'name' => 'Alice',
            'email' => 'taken@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_rejects_password_mismatch(): void
    {
        $this->postJson(route('register'), [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'secret-password',
            'password_confirmation' => 'different-password',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
