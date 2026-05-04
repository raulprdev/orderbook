<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\Symbol;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
final class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'symbol' => Symbol::BTC,
            'amount' => 0,
            'locked_amount' => 0,
        ];
    }

    public function forSymbol(Symbol $symbol): self
    {
        return $this->state(['symbol' => $symbol]);
    }

    public function withAmountSubunit(int $subunit): self
    {
        return $this->state(['amount' => $subunit]);
    }

    public function withLockedSubunit(int $subunit): self
    {
        return $this->state(['locked_amount' => $subunit]);
    }
}