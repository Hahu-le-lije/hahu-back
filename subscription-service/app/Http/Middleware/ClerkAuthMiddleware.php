<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\GenericUser;
use Strobotti\JWK\KeySetFactory;
use Strobotti\JWK\KeyConverter;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ClerkAuthMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                "status" => "error",
                'error' => 'No authorization token provided'
            ], 401);
        }

        try {
            // 1. Get the PEM key from cache or fetch/convert it if not exists
            // We cache it for 24 hours (86400 seconds)
            $pem = Cache::remember('clerk_public_key_pem', 86400, function () {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . config('services.clerk.secret_key'),
                ])->get('https://api.clerk.com/v1/jwks');

                if ($response->failed()) {
                    throw new \Exception('Could not fetch JWKS from Clerk');
                }

                $keySetFactory = new KeySetFactory();
                $keySet = $keySetFactory->createFromJSON($response->body());

                // Get the first key from the set
                $keys = $keySet->getKeys();
                $firstKey = reset($keys);

                if (!$firstKey) {
                    throw new \Exception('No keys found in Clerk JWKS');
                }

                // Convert JWK to PEM format
                return (new KeyConverter())->keyToPem($firstKey);
            });

            // 2. Decode the JWT using the PEM key
            // Note: decoded is an object by default in Firebase JWT
            $decoded = JWT::decode($token, new Key($pem, 'RS256'));

            // 3. Map the decoded payload to a GenericUser
            // This allows you to use Auth::user() throughout the request
            Auth::setUser(new GenericUser([
                'id' => $decoded->sub,
                'email' => $decoded->email ?? null,
                'name' => trim(($decoded->first_name ?? '') . ' ' . ($decoded->last_name ?? '')) ?: null,
            ]));

            return $next($request);

        } catch (\Firebase\JWT\ExpiredException $e) {
            return response()->json([
                'status' => 'error',
                'error' => 'Token has expired'
            ], 401);
        } catch (\Exception $e) {
            Cache::forget('clerk_public_key_pem');

            return response()->json([
                'status' => 'error',
                'error' => 'Unauthorized',
                'message' => 'Token validation failed'
            ], 401);
        }
    }
}