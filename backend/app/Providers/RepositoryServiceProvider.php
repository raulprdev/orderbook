<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Matching\FirstValidMatchStrategy;
use App\Domain\Matching\MatchingStrategy;
use App\Repositories\Contracts\AssetRepository;
use App\Repositories\Contracts\OrderRepository;
use App\Repositories\Contracts\TradeRepository;
use App\Repositories\Contracts\UserRepository;
use App\Repositories\EloquentAssetRepository;
use App\Repositories\EloquentOrderRepository;
use App\Repositories\EloquentTradeRepository;
use App\Repositories\EloquentUserRepository;
use App\Services\CancelOrderService;
use App\Services\MatchOrderService;
use App\Services\PlaceOrderService;
use Illuminate\Support\ServiceProvider;

final class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(OrderRepository::class, EloquentOrderRepository::class);
        $this->app->bind(AssetRepository::class, EloquentAssetRepository::class);
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(TradeRepository::class, EloquentTradeRepository::class);
        $this->app->bind(MatchingStrategy::class, FirstValidMatchStrategy::class);

        $this->app->when([MatchOrderService::class, PlaceOrderService::class, CancelOrderService::class])
            ->needs('$commissionBasisPoints')
            ->giveConfig('orderbook.commission_basis_points');
    }
}
