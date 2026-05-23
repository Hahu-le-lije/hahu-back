<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class JwtAuthenticate
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, ?string $audience = null, ?string $requiredScope = null): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            $this->logRejectedToken($request, 'missing_token');

            return response()->json(['error' => 'No token provided'], 401);
        }

        try {
            $secret = (string) config('auth.jwt_secret', env('JWT_SECRET', ''));

            if ($secret === '') {
                $this->logRejectedToken($request, 'missing_jwt_secret');

                return response()->json(['error' => 'JWT secret not configured'], 500);
            }

            [$header, $payload] = $this->decodeToken($token);

            if (($header['alg'] ?? null) !== 'HS256') {
                $this->logRejectedToken($request, 'invalid_algorithm');

                return response()->json(['error' => 'Invalid token'], 401);
            }

            $expectedSignature = $this->sign($this->tokenSigningInput($token), $secret);
            $actualSignature = $this->base64UrlDecode(explode('.', $token)[2] ?? '');

            if (! hash_equals($expectedSignature, $actualSignature)) {
                $this->logRejectedToken($request, 'invalid_signature');

                return response()->json(['error' => 'Invalid token'], 401);
            }

            if (! isset($payload['exp']) || (int) $payload['exp'] < time()) {
                $this->logRejectedToken($request, 'expired_token');

                return response()->json(['error' => 'Token expired'], 401);
            }

            if ($audience !== null && (string) ($payload['aud'] ?? '') !== $audience) {
                $this->logRejectedToken($request, 'audience_mismatch', ['expected_aud' => $audience, 'token_aud' => $payload['aud'] ?? null]);

                return response()->json(['error' => 'Invalid token audience'], 401);
            }

            if ($requiredScope !== null && ! $this->hasScope((string) ($payload['scope'] ?? ''), $requiredScope)) {
                $this->logRejectedToken($request, 'scope_missing', ['required_scope' => $requiredScope, 'token_scope' => $payload['scope'] ?? null]);

                return response()->json(['error' => 'Insufficient token scope'], 401);
            }

            $userId = $payload['user_id'] ?? $payload['sub'] ?? null;
            if (! $userId) {
                $this->logRejectedToken($request, 'missing_subject');

                return response()->json(['error' => 'Invalid token payload'], 401);
            }

            $user = new User();
            $user->id = $userId;
            $user->name = trim((string) ($payload['first_name'] ?? '') . ' ' . (string) ($payload['last_name'] ?? '')) ?: (string) ($payload['service'] ?? 'ServiceUser');

            Auth::setUser($user);

        } catch (\Throwable $e) {
            $this->logRejectedToken($request, 'exception', ['message' => $e->getMessage()]);

            return response()->json(['error' => 'Invalid token'], 401);
        }

        return $next($request);
    }

    private function decodeToken(string $token): array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            throw new \RuntimeException('Malformed JWT');
        }

        $header = json_decode($this->base64UrlDecode($parts[0]), true);
        $payload = json_decode($this->base64UrlDecode($parts[1]), true);

        if (! is_array($header) || ! is_array($payload)) {
            throw new \RuntimeException('Invalid JWT encoding');
        }

        return [$header, $payload];
    }

    private function tokenSigningInput(string $token): string
    {
        return implode('.', array_slice(explode('.', $token), 0, 2));
    }

    private function sign(string $input, string $secret): string
    {
        return hash_hmac('sha256', $input, $secret, true);
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;

        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/')) ?: '';
    }

    private function hasScope(string $scopeString, string $requiredScope): bool
    {
        $scopes = preg_split('/\s+/', trim($scopeString)) ?: [];

        return in_array($requiredScope, $scopes, true);
    }

    private function logRejectedToken(Request $request, string $reason, array $context = []): void
    {
        Log::warning('CMS shared JWT rejected', array_merge([
            'reason' => $reason,
            'ip' => $request->ip(),
            'path' => $request->path(),
            'method' => $request->method(),
            'has_token' => $request->bearerToken() !== null,
        ], $context));
    }
}
