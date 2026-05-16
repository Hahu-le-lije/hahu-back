<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSubscriptionService
{
    public function handle(Request $request, Closure $next): Response
    {
        $expectedToken = config('child_service.subscription_service_token');

        if (! is_string($expectedToken) || $expectedToken === '') {
            return response()->json(['message' => 'Subscription service token is not configured.'], 503);
        }

        $token = $request->bearerToken();

        if (! is_string($token) || ! hash_equals($expectedToken, $token)) {
            return response()->json(['message' => 'Invalid subscription service token.'], 401);
        }

        return $next($request);
    }
}
