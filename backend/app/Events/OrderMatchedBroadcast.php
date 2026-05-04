<?php

declare(strict_types=1);

namespace App\Events;

use App\Domain\Events\OrderMatched;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class OrderMatchedBroadcast implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public readonly OrderMatched $event) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->event->buyerUserId}"),
            new PrivateChannel("user.{$this->event->sellerUserId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.matched';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'buy_order_id' => $this->event->buyOrderId,
            'sell_order_id' => $this->event->sellOrderId,
            'buyer_user_id' => $this->event->buyerUserId,
            'seller_user_id' => $this->event->sellerUserId,
            'symbol' => $this->event->symbol->value,
            'price' => $this->event->matchPrice->toUsd(),
            'amount' => $this->event->matchAmount->toDecimal(),
            'volume' => $this->event->volume->toUsd(),
            'fee' => $this->event->fee->toUsd(),
        ];
    }
}