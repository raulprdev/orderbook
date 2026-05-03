<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\User;
use App\Domain\Exceptions\InsufficientBalance;
use App\Domain\ValueObjects\Money;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function test_debit_reduces_balance(): void
    {
        $user = $this->makeUser(balance: Money::fromUsd('1000.00'));

        $user->debit(Money::fromUsd('300.00'));

        $this->assertSame(700_000_000, $user->balance()->microUsd());
    }

    public function test_debit_throws_when_insufficient_balance(): void
    {
        $user = $this->makeUser(balance: Money::fromUsd('100.00'));

        $this->expectException(InsufficientBalance::class);
        $user->debit(Money::fromUsd('500.00'));
    }

    public function test_credit_increases_balance(): void
    {
        $user = $this->makeUser(balance: Money::fromUsd('1000.00'));

        $user->credit(Money::fromUsd('250.00'));

        $this->assertSame(1_250_000_000, $user->balance()->microUsd());
    }

    private function makeUser(Money $balance): User
    {
        return new User(id: 1, balance: $balance);
    }
}
