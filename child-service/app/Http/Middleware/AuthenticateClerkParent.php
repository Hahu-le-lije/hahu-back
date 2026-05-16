<?php

namespace App\Http\Middleware;

use App\Services\ClerkTokenVerifier;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AuthenticateClerkParent
{
    public function __construct(private readonly ClerkTokenVerifier $clerk)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null) {
            return response()->json(['message' => 'Missing bearer token.'], 401);
        }

        try {
            $claims = $this->clerk->verify($token);
        } catch (Throwable) {
            return response()->json(['message' => 'Invalid Clerk token.'], 401);
        }

        $parentId = $claims['sub'] ?? null;

        if ($parentId === null) {
            return response()->json(['message' => 'Clerk token is missing user id.'], 403);
        }

        $request->attributes->set('parent_id', (string) $parentId);
        $request->attributes->set('parent_claims', $claims);

        return $next($request);
    }
}
