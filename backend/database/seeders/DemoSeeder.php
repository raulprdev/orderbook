<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Symbol;
use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $alice = User::firstOrCreate(
            ['email' => 'alice@example.com'],
            [
                'name' => 'Alice',
                'password' => 'secret-password',
                'balance' => 100_000_000_000, // $100,000 in micro-USD
            ]
        );

        $bob = User::firstOrCreate(
            ['email' => 'bob@example.com'],
            [
                'name' => 'Bob',
                'password' => 'secret-password',
                'balance' => 0,
            ]
        );

        Asset::firstOrCreate(
            ['user_id' => $bob->id, 'symbol' => Symbol::BTC],
            ['amount' => 100_000_000, 'locked_amount' => 0] // 1 BTC
        );

        Asset::firstOrCreate(
            ['user_id' => $alice->id, 'symbol' => Symbol::ETH],
            ['amount' => 500_000_000, 'locked_amount' => 0] // 5 ETH
        );
    }
}
