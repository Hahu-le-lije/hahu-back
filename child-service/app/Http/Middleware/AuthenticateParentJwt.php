<?php

namespace App\Http\Middleware;

use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthenticateParentJwt
{
    public function __construct(private readonly JwtService $jwt)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null) {
            return response()->json(['message' => 'Missing bearer token.'], 401);
        }

        try {
            $claims = $this->jwt->verify(
                $token,
                config('child_service.parent_jwt_secret'),
                config('child_service.parent_token_audience')
            );
        } catch (Throwable) {
            return response()->json(['message' => 'Invalid parent token.'], 401);
        }

        $parentId = $claims['parent_id'] ?? $claims['sub'] ?? null;

        if ($parentId === null || ($claims['role'] ?? 'parent') !== 'parent') {
            return response()->json(['message' => 'Token is not a parent token.'], 403);
        }

        $request->attributes->set('parent_id', (string) $parentId);
        $request->attributes->set('parent_claims', $claims);

        return $next($request);
    }
}
