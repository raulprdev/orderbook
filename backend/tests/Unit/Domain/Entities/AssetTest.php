<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Entities;

use App\Domain\Entities\Asset;
use App\Domain\Exceptions\InsufficientAsset;
use App\Domain\ValueObjects\Amount;
use App\Enums\Symbol;
use PHPUnit\Framework\TestCase;

final class AssetTest extends TestCase
{
    public function test_lock_moves_amount_from_available_to_locked(): void
    {
        $asset = $this->makeAsset(
            amount: Amount::fromDecimal('1.0'),
            locked: Amount::fromDecimal('0.0'),
        );

        $asset->lock(Amount::fromDecimal('0.3'));

        $this->assertSame(70_000_000, $asset->amount()->subunit());
        $this->assertSame(30_000_000, $asset->lockedAmount()->subunit());
    }

    public function test_lock_throws_when_insufficient_available(): void
    {
        $asset = $this->makeAsset(
            amount: Amount::fromDecimal('0.1'),
            locked: Amount::fromDecimal('0.0'),
        );

        $this->expectException(InsufficientAsset::class);
        $asset->lock(Amount::fromDecimal('0.5'));
    }

    public function test_release_moves_amount_from_locked_to_available(): void
    {
        $asset = $this->makeAsset(
            amount: Amount::fromDecimal('0.5'),
            locked: Amount::fromDecimal('0.5'),
        );

        $asset->release(Amount::fromDecimal('0.2'));

        $this->assertSame(70_000_000, $asset->amount()->subunit());
        $this->assertSame(30_000_000, $asset->lockedAmount()->subunit());
    }

    public function test_release_throws_when_insufficient_locked(): void
    {
        $asset = $this->makeAsset(
            amount: Amount::fromDecimal('1.0'),
            locked: Amount::fromDecimal('0.1'),
        );

        $this->expectException(InsufficientAsset::class);
        $asset->release(Amount::fromDecimal('0.5'));
    }

    public function test_debit_reduces_locked(): void
    {
        $asset = $this->makeAsset(
            amount: Amount::fromDecimal('1.0'),
            locked: Amount::fromDecimal('0.5'),
        );

        $asset->debit(Amount::fromDecimal('0.2'));

        $this->assertSame(100_000_000, $asset->amount()->subunit());
        $this->assertSame(30_000_000, $asset->lockedAmount()->subunit());
    }

    public function test_debit_throws_when_insufficient_locked(): void
    {
        $asset = $this->makeAsset(
            amount: Amount::fromDecimal('1.0'),
            locked: Amount::fromDecimal('0.1'),
        );

        $this->expectException(InsufficientAsset::class);
        $asset->debit(Amount::fromDecimal('0.5'));
    }

    public function test_credit_increases_available(): void
    {
        $asset = $this->makeAsset(
            amount: Amount::fromDecimal('1.0'),
            locked: Amount::fromDecimal('0.0'),
        );

        $asset->credit(Amount::fromDecimal('0.3'));

        $this->assertSame(130_000_000, $asset->amount()->subunit());
        $this->assertSame(0, $asset->lockedAmount()->subunit());
    }

    private function makeAsset(Amount $amount, Amount $locked): Asset
    {
        return new Asset(
            id: 1,
            userId: 100,
            symbol: Symbol::BTC,
            amount: $amount,
            lockedAmount: $locked,
        );
    }
}
