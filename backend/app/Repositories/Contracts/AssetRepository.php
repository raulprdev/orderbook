<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Domain\Entities\Asset;
use App\Enums\Symbol;

interface AssetRepository
{
    public function save(Asset $asset): Asset;

    public function findByUserAndSymbol(int $userId, Symbol $symbol): ?Asset;

    public function findByUserAndSymbolForUpdate(int $userId, Symbol $symbol): ?Asset;

    public function findOrCreateForUpdate(int $userId, Symbol $symbol): Asset;

    /**
     * @return array<int, Asset>
     */
    public function userAssets(int $userId): array;
}
