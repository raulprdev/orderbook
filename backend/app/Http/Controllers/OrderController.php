<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DataTransferObjects\PlaceOrderData;
use App\Enums\Symbol;
use App\Http\Requests\PlaceOrderRequest;
use App\Http\Resources\OrderResource;
use App\Repositories\Contracts\OrderRepository;
use App\Services\PlaceOrderService;
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

        return response()->json([
            'orders' => OrderResource::collection(
                $orders->openOrdersForSymbol(Symbol::from($validated['symbol']))
            ),
        ]);
    }

    public function store(PlaceOrderRequest $request, PlaceOrderService $placeOrder): JsonResponse
    {
        $order = $placeOrder(
            data: PlaceOrderData::from($request->validated()),
            userId: $request->user()->id,
        );

        return response()->json([
            'order' => OrderResource::make($order),
        ], 201);
    }
}
