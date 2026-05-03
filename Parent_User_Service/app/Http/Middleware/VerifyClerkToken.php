<?php

namespace App\Http\Middleware;

use App\Models\ParentUser;
use Closure;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyClerkToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Missing authorization token.'], 401);
        }

        $jwksUrl = config('services.clerk.jwks_url');
        $issuer = config('services.clerk.issuer');

        if (! $jwksUrl || ! $issuer) {
            return response()->json(['message' => 'Clerk configuration is missing.'], 500);
        }

        try {
            $verifySsl = (bool) config('services.clerk.jwks_verify', true);
            $jwks = Cache::remember('clerk.jwks', 3600, function () use ($jwksUrl, $verifySsl) {
                $client = Http::withOptions(['verify' => $verifySsl]);

                return $client->get($jwksUrl)->throw()->json();
            });

            $keys = JWK::parseKeySet($jwks);
            JWT::$leeway = 60;
            $payload = JWT::decode($token, $keys);
        } catch (\Throwable $exception) {
            Log::warning('Clerk token verification failed.', [
                'error' => $exception->getMessage(),
            ]);
            return response()->json(['message' => 'Invalid or expired token.'], 401);
        }

        if (($payload->iss ?? null) !== $issuer) {
            return response()->json(['message' => 'Invalid token issuer.'], 401);
        }

        $clerkId = $payload->sub ?? null;

        if (! $clerkId) {
            return response()->json(['message' => 'Invalid token subject.'], 401);
        }

        $parent = ParentUser::where('clerk_id', $clerkId)->first();

        if (! $parent) {
            return response()->json(['message' => 'Parent not found.'], 401);
        }

        $request->attributes->set('parent', $parent);
        $request->setUserResolver(fn () => $parent);

        return $next($request);
    }
}
