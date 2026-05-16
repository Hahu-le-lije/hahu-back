<?php

namespace App\Http\Controllers;

use App\Models\Child;
use App\Services\JwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ChildAuthController extends Controller
{
    public function __construct(private readonly JwtService $jwt)
    {
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $child = Child::query()
            ->where('username', $credentials['username'])
            ->where('status', 'active')
            ->first();

        if ($child === null || ! Hash::check($credentials['password'], $child->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 422);
        }

        $child->forceFill(['last_login_at' => now()])->save();

        $token = $this->jwt->issue([
            'sub' => (string) $child->getKey(),
            'parent_id' => $child->parent_id,
            'role' => 'child',
            'aud' => config('child_service.child_token_audience'),
        ], config('child_service.child_jwt_secret'), config('child_service.child_token_ttl_minutes'));

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('child_service.child_token_ttl_minutes') * 60,
            'child' => $child->refresh(),
        ]);
    }

    public function logout(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
