<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Domain\Entities\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read Order $resource
 */
final class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id(),
            'symbol' => $this->resource->symbol()->value,
            'side' => $this->resource->side()->value,
            'price' => $this->resource->price()->toUsd(),
            'amount' => $this->resource->amount()->toDecimal(),
            'status' => $this->resource->status()->value,
            'created_at' => $this->resource->createdAt()?->format(DATE_ATOM),
        ];
    }
}