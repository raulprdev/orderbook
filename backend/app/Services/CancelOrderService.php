<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Entities\Order;
use App\Domain\Exceptions\CannotCancelOrder;
use App\Domain\ValueObjects\Money;
use App\Enums\Side;
use App\Repositories\Contracts\AssetRepository;
use App\Repositories\Contracts\OrderRepository;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Database\ConnectionInterface;

final class CancelOrderService
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly UserRepository $users,
        private readonly AssetRepository $assets,
        private readonly ConnectionInterface $db,
        private readonly int $commissionBasisPoints,
    ) {}

    public function __invoke(int $orderId, int $userId): Order
    {
        return $this->db->transaction(
            fn () => $this->cancel($orderId, $userId)
        );
    }

    private function cancel(int $orderId, int $userId): Order
    {
        $order = $this->orders->findOpenForUpdate($orderId);
        if ($order === null || $order->userId() !== $userId) {
            throw new CannotCancelOrder(sprintf('Order %d cannot be cancelled', $orderId));
        }

        $order->side() === Side::Buy
            ? $this->refundBuyer($order)
            : $this->releaseSellerAsset($order);

        $order->cancel();
        $this->orders->save($order);

        return $order;
    }

    private function refundBuyer(Order $order): void
    {
        $user = $this->users->findByIdForUpdate($order->userId());
        $volume = Money::fromVolume($order->price(), $order->amount());
        $fee = $volume->applyBasisPoints($this->commissionBasisPoints);
        $totalLock = $volume->plus($fee);

        $user->credit($totalLock);
        $this->users->save($user);
    }

    private function releaseSellerAsset(Order $order): void
    {
        $asset = $this->assets->findByUserAndSymbolForUpdate($order->userId(), $order->symbol());
        $asset->release($order->amount());
        $this->assets->save($asset);
    }
}