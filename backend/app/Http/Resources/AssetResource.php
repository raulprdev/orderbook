<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Entities\Asset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Asset $resource
 */
final class AssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'symbol' => $this->resource->symbol()->value,
            'amount' => $this->resource->amount()->toDecimal(),
            'locked_amount' => $this->resource->lockedAmount()->toDecimal(),
        ];
    }
}
