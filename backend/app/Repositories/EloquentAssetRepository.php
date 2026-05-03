<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Entities\Asset as DomainAsset;
use App\Domain\ValueObjects\Amount;
use App\Enums\Symbol;
use App\Models\Asset as EloquentAsset;
use App\Repositories\Contracts\AssetRepository;
use Illuminate\Support\Facades\DB;

final class EloquentAssetRepository implements AssetRepository
{
    public function save(DomainAsset $asset): DomainAsset
    {
        $eloquent = $asset->id() === null
            ? new EloquentAsset()
            : EloquentAsset::findOrFail($asset->id());

        $this->applyToEloquent($asset, $eloquent);
        $eloquent->save();

        return $this->toDomain($eloquent);
    }

    public function findByUserAndSymbol(int $userId, Symbol $symbol): ?DomainAsset
    {
        return $this->toDomain($this->baseQuery($userId, $symbol)->first());
    }

    public function findByUserAndSymbolForUpdate(int $userId, Symbol $symbol): ?DomainAsset
    {
        return $this->toDomain(
            $this->baseQuery($userId, $symbol)->lockForUpdate()->first()
        );
    }

    public function findOrCreateForUpdate(int $userId, Symbol $symbol): DomainAsset
    {
        DB::statement(
            'INSERT INTO assets (user_id, symbol, amount, locked_amount, created_at, updated_at)
             VALUES (?, ?, 0, 0, NOW(), NOW())
             ON CONFLICT (user_id, symbol) DO NOTHING',
            [$userId, $symbol->value]
        );

        return $this->toDomain(
            $this->baseQuery($userId, $symbol)->lockForUpdate()->first()
        );
    }

    public function userAssets(int $userId): array
    {
        return EloquentAsset::query()
            ->where('user_id', $userId)
            ->orderBy('symbol')
            ->get()
            ->map(fn (EloquentAsset $a) => $this->toDomain($a))
            ->all();
    }

    private function baseQuery(int $userId, Symbol $symbol)
    {
        return EloquentAsset::query()
            ->where('user_id', $userId)
            ->where('symbol', $symbol);
    }

    private function toDomain(?EloquentAsset $eloquent): ?DomainAsset
    {
        if ($eloquent === null) {
            return null;
        }

        return new DomainAsset(
            id: $eloquent->id,
            userId: $eloquent->user_id,
            symbol: $eloquent->symbol,
            amount: Amount::fromSubunit($eloquent->amount),
            lockedAmount: Amount::fromSubunit($eloquent->locked_amount),
        );
    }

    private function applyToEloquent(DomainAsset $asset, EloquentAsset $eloquent): void
    {
        $eloquent->user_id = $asset->userId();
        $eloquent->symbol = $asset->symbol();
        $eloquent->amount = $asset->amount()->subunit();
        $eloquent->locked_amount = $asset->lockedAmount()->subunit();
    }
}