<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\OrderResource;
use App\Repositories\Contracts\OrderRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class MyOrdersController extends Controller
{
    public function __invoke(Request $request, OrderRepository $orders): JsonResponse
    {
        return response()->json([
            'orders' => OrderResource::collection(
                $orders->userOrders($request->user()->id)
            ),
        ]);
    }
}