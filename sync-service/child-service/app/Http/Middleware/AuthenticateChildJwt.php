<?php

namespace App\Http\Middleware;

use App\Models\Child;
use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthenticateChildJwt
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
                config('child_service.child_jwt_secret'),
                config('child_service.child_token_audience')
            );
        } catch (Throwable) {
            return response()->json(['message' => 'Invalid child token.'], 401);
        }

        if (($claims['role'] ?? null) !== 'child' || ! isset($claims['sub'])) {
            return response()->json(['message' => 'Token is not a child token.'], 403);
        }

        $child = Child::query()
            ->whereKey($claims['sub'])
            ->where('status', 'active')
            ->first();

        if ($child === null) {
            return response()->json(['message' => 'Child account is unavailable.'], 401);
        }

        $request->attributes->set('child_claims', $claims);
        $request->setUserResolver(fn () => $child);

        return $next($request);
    }
}
