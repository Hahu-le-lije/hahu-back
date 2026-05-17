<?php

namespace App\Http\Middleware;

use App\Services\ServiceJwtService;
use Closure;
use Illuminate\Http\Request;

class VerifyServiceJwt
{
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json([
                'message' => 'Missing token'
            ], 401);
        }

        $token = substr($authHeader, 7);
        $internalTokens = config('services.internal_service_tokens', []);

        foreach ($internalTokens as $internalToken) {
            if (is_string($internalToken) && $internalToken !== '' && hash_equals($internalToken, $token)) {
                $request->attributes->set('service_identity', 'internal');

                return $next($request);
            }
        }

        $service = new ServiceJwtService();

        try {
            $decoded = $service->validateToken(
                $token,
                'sync-service'
            );

            // attach identity to request
            $request->attributes->set(
                'service_identity',
                $decoded->iss
            );

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Invalid service token',
                'error' => $e->getMessage()
            ], 401);
        }

        return $next($request);
    }
}
