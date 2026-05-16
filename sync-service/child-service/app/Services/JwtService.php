<?php

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;

class JwtService
{
    public function issue(array $claims, string $secret, int $ttlMinutes): string
    {
        $now = time();

        return $this->encode([
            ...$claims,
            'iat' => $now,
            'exp' => $now + ($ttlMinutes * 60),
        ], $secret);
    }

    public function encode(array $claims, string $secret): string
    {
        $this->assertSecret($secret);

        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT',
        ];

        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode(json_encode($claims, JSON_THROW_ON_ERROR)),
        ];

        $signature = hash_hmac('sha256', implode('.', $segments), $this->normalizeSecret($secret), true);
        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    public function verify(string $token, string $secret, ?string $audience = null): array
    {
        $this->assertSecret($secret);

        $segments = explode('.', $token);

        if (count($segments) !== 3) {
            throw new InvalidArgumentException('Invalid token format.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $segments;
        $header = json_decode($this->base64UrlDecode($encodedHeader), true, 512, JSON_THROW_ON_ERROR);
        $payload = json_decode($this->base64UrlDecode($encodedPayload), true, 512, JSON_THROW_ON_ERROR);

        if (($header['alg'] ?? null) !== 'HS256') {
            throw new InvalidArgumentException('Unsupported token algorithm.');
        }

        $expected = hash_hmac('sha256', $encodedHeader.'.'.$encodedPayload, $this->normalizeSecret($secret), true);

        if (! hash_equals($expected, $this->base64UrlDecode($encodedSignature))) {
            throw new InvalidArgumentException('Invalid token signature.');
        }

        if (isset($payload['exp']) && (int) $payload['exp'] < time()) {
            throw new InvalidArgumentException('Token has expired.');
        }

        if ($audience !== null && isset($payload['aud']) && $payload['aud'] !== $audience) {
            throw new InvalidArgumentException('Invalid token audience.');
        }

        return $payload;
    }

    private function assertSecret(?string $secret): void
    {
        if ($secret === null || trim($secret) === '') {
            throw new RuntimeException('JWT secret is not configured.');
        }
    }

    private function normalizeSecret(string $secret): string
    {
        if (str_starts_with($secret, 'base64:')) {
            return base64_decode(substr($secret, 7), true) ?: $secret;
        }

        return $secret;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $value): string
    {
        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        if ($decoded === false) {
            throw new InvalidArgumentException('Invalid base64url token segment.');
        }

        return $decoded;
    }
}
