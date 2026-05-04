<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Repositories\Contracts\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final class RegisterController extends Controller
{
    public function __invoke(RegisterRequest $request, UserRepository $users): JsonResponse
    {
        $userId = $users->register(
            name: $request->validated('name'),
            email: $request->validated('email'),
            password: $request->validated('password'),
        );

        Auth::loginUsingId($userId);
        $request->session()->regenerate();

        return response()->json([
            'user' => [
                'id' => $userId,
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
            ],
        ], 201);
    }
}
