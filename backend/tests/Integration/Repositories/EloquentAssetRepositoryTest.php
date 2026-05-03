<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Domain\Entities\Asset as DomainAsset;
use App\Domain\ValueObjects\Amount;
use App\Enums\Symbol;
use App\Models\User;
use App\Repositories\EloquentAssetRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EloquentAssetRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentAssetRepository $repository;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentAssetRepository;
        $this->user = User::factory()->create();
    }

    public function test_save_persists_new_asset_and_returns_with_id(): void
    {
        $asset = $this->makeAsset();

        $saved = $this->repository->save($asset);

        $this->assertNotNull($saved->id());
        $this->assertSame($this->user->id, $saved->userId());
        $this->assertSame(Symbol::BTC, $saved->symbol());
        $this->assertSame(100_000_000, $saved->amount()->subunit());
        $this->assertSame(0, $saved->lockedAmount()->subunit());
    }

    public function test_save_updates_existing_asset_after_lock(): void
    {
        $saved = $this->repository->save($this->makeAsset());

        $persisted = $this->repository->findByUserAndSymbol($this->user->id, Symbol::BTC);
        $persisted->lock(Amount::fromDecimal('0.3'));
        $this->repository->save($persisted);

        $reread = $this->repository->findByUserAndSymbol($this->user->id, Symbol::BTC);
        $this->assertSame(70_000_000, $reread->amount()->subunit());
        $this->assertSame(30_000_000, $reread->lockedAmount()->subunit());
    }

    public function test_find_by_user_and_symbol_returns_null_when_missing(): void
    {
        $this->assertNull(
            $this->repository->findByUserAndSymbol($this->user->id, Symbol::BTC)
        );
    }

    public function test_find_or_create_returns_existing_asset(): void
    {
        $existing = $this->repository->save($this->makeAsset(amount: '0.5'));

        $found = $this->repository->findOrCreateForUpdate($this->user->id, Symbol::BTC);

        $this->assertSame($existing->id(), $found->id());
        $this->assertSame(50_000_000, $found->amount()->subunit());
    }

    public function test_find_or_create_creates_zero_balance_when_missing(): void
    {
        $created = $this->repository->findOrCreateForUpdate($this->user->id, Symbol::ETH);

        $this->assertNotNull($created->id());
        $this->assertSame(Symbol::ETH, $created->symbol());
        $this->assertSame(0, $created->amount()->subunit());
        $this->assertSame(0, $created->lockedAmount()->subunit());
    }

    public function test_user_assets_returns_all_user_assets(): void
    {
        $this->repository->save($this->makeAsset(symbol: Symbol::BTC, amount: '0.5'));
        $this->repository->save($this->makeAsset(symbol: Symbol::ETH, amount: '12.0'));

        $assets = $this->repository->userAssets($this->user->id);

        $this->assertCount(2, $assets);
    }

    private function makeAsset(
        Symbol $symbol = Symbol::BTC,
        string $amount = '1.0',
        string $locked = '0',
    ): DomainAsset {
        return new DomainAsset(
            id: null,
            userId: $this->user->id,
            symbol: $symbol,
            amount: Amount::fromDecimal($amount),
            lockedAmount: Amount::fromDecimal($locked),
        );
    }
}
