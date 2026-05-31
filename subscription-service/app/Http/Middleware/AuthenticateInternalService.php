<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateInternalService
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = config('services.subscription_service.internal_service_token');

        if (! is_string($expectedToken) || $expectedToken === '') {
            return response()->json(['message' => 'Internal service token is not configured.'], 503);
        }

        $token = $request->bearerToken();

        if (! is_string($token) || ! hash_equals($expectedToken, $token)) {
            return response()->json(['message' => 'Invalid internal service token.'], 401);
        }

        return $next($request);
    }
}
