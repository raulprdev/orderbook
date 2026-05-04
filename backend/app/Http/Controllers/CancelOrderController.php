<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Services\CancelOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CancelOrderController extends Controller
{
    public function __invoke(string $order, Request $request, CancelOrderService $cancel): JsonResponse
    {
        $cancelled = $cancel(
            orderId: (int) $order,
            userId: $request->user()->id,
        );

        return response()->json([
            'order' => OrderResource::make($cancelled),
        ]);
    }
}