<?php

declare(strict_types=1);

namespace Tests\Integration\Repositories;

use App\Domain\ValueObjects\Money;
use App\Models\User as EloquentUser;
use App\Repositories\EloquentUserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class EloquentUserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentUserRepository;
    }

    public function test_save_persists_balance_change(): void
    {
        $eloquent = EloquentUser::factory()->create(['balance' => 1_000_000_000]);

        $domain = $this->repository->findById($eloquent->id);
        $domain->debit(Money::fromUsd('250.00'));
        $this->repository->save($domain);

        $eloquent->refresh();
        $this->assertSame(750_000_000, $eloquent->balance);
    }

    public function test_find_by_id_returns_user(): void
    {
        $eloquent = EloquentUser::factory()->create(['balance' => 500_000_000]);

        $found = $this->repository->findById($eloquent->id);

        $this->assertNotNull($found);
        $this->assertSame($eloquent->id, $found->id());
        $this->assertSame(500_000_000, $found->balance()->microUsd());
    }

    public function test_find_by_id_returns_null_for_missing(): void
    {
        $this->assertNull($this->repository->findById(999_999));
    }

    public function test_find_by_id_for_update_returns_user(): void
    {
        $eloquent = EloquentUser::factory()->create(['balance' => 500_000_000]);

        $found = $this->repository->findByIdForUpdate($eloquent->id);

        $this->assertNotNull($found);
        $this->assertSame($eloquent->id, $found->id());
    }
}
