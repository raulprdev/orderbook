<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Entities\Order;
use App\Enums\Symbol;
use App\Repositories\Contracts\OrderRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class OrderController extends Controller
{
    public function index(Request $request, OrderRepository $orders): JsonResponse
    {
        $validated = $request->validate([
            'symbol' => ['required', Rule::enum(Symbol::class)],
        ]);

        $symbol = Symbol::from($validated['symbol']);

        return response()->json([
            'orders' => array_map(
                fn (Order $order) => [
                    'id' => $order->id(),
                    'side' => $order->side()->value,
                    'price' => $order->price()->toUsd(),
                    'amount' => $order->amount()->toDecimal(),
                ],
                $orders->openOrdersForSymbol($symbol),
            ),
        ]);
    }
}