<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\ValueObjects\Money;
use App\Http\Resources\AssetResource;
use App\Repositories\Contracts\AssetRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProfileController extends Controller
{
    public function __invoke(Request $request, AssetRepository $assets): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'balance' => Money::fromMicroUsd($user->balance)->toUsd(),
            'assets' => AssetResource::collection($assets->userAssets($user->id)),
        ]);
    }
}
