<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthenticate
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        if (! $token) {
            return $this->rejectToken($request, 'missing_token', 'No token provided', 401);
        }

        $secret = (string) config('auth.jwt_secret', env('JWT_SECRET', ''));
        if ($secret === '') {
            return $this->rejectToken($request, 'missing_jwt_secret', 'JWT secret not configured', 500);
        }

        try {
            $jwt = $this->decodeToken($token);

            if (($jwt['header']['alg'] ?? null) !== 'HS256') {
                return $this->rejectToken($request, 'invalid_algorithm', 'Invalid token', 401);
            }

            $expectedSignature = hash_hmac('sha256', $this->tokenSigningInput($token), $secret, true);
            $actualSignature = $this->base64UrlDecode(explode('.', $token)[2] ?? '');

            if (! hash_equals($expectedSignature, $actualSignature)) {
                return $this->rejectToken($request, 'invalid_signature', 'Invalid token', 401);
            }

            Auth::setUser($this->buildUserFromPayload($request, $jwt['payload']));
        } catch (\Throwable $e) {
            return $this->rejectToken($request, 'exception', 'Invalid token', 401, ['message' => $e->getMessage()]);
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

        return [
            'header' => $header,
            'payload' => $payload,
        ];
    }

    private function buildUserFromPayload(Request $request, array $payload): User
    {
        $userId = $payload['user_id'] ?? $payload['sub'] ?? null;

        if (! $userId) {
            $this->rejectToken($request, 'missing_subject', 'Invalid token payload', 401);
        }

        $user = new User();
        $user->id = $userId;
        $user->name = trim((string) ($payload['first_name'] ?? '') . ' ' . (string) ($payload['last_name'] ?? ''))
            ?: (string) ($payload['service'] ?? 'ServiceUser');

        return $user;
    }

    private function tokenSigningInput(string $token): string
    {
        return implode('.', array_slice(explode('.', $token), 0, 2));
    }

    private function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;

        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode(strtr($data, '-_', '+/')) ?: '';
    }

    private function rejectToken(Request $request, string $reason, string $message, int $status, array $context = []): Response
    {
        $this->logRejectedToken($request, $reason, $context);

        return response()->json(['error' => $message], $status);
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
