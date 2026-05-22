<?php

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;

class ClerkTokenVerifier
{
    public function verify(string $token): array
    {
        $segments = explode('.', $token);

        if (count($segments) !== 3) {
            throw new InvalidArgumentException('Invalid Clerk token format.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $segments;

        $header = json_decode($this->base64UrlDecode($encodedHeader), true, 512, JSON_THROW_ON_ERROR);
        $payload = json_decode($this->base64UrlDecode($encodedPayload), true, 512, JSON_THROW_ON_ERROR);

        if (($header['alg'] ?? null) !== 'RS256') {
            throw new InvalidArgumentException('Unsupported Clerk token algorithm.');
        }

        $publicKey = config('child_service.clerk_jwt_key');

        if ($publicKey === null || trim($publicKey) === '') {
            throw new RuntimeException('Clerk JWT public key is not configured.');
        }

        $verified = openssl_verify(
            $encodedHeader.'.'.$encodedPayload,
            $this->base64UrlDecode($encodedSignature),
            $this->normalizePublicKey($publicKey),
            OPENSSL_ALGO_SHA256
        );

        if ($verified !== 1) {
            throw new InvalidArgumentException('Invalid Clerk token signature.');
        }

        $this->assertRegisteredClaims($payload);

        return $payload;
    }

    private function assertRegisteredClaims(array $payload): void
    {
        $now = time();

        if (! isset($payload['sub']) || $payload['sub'] === '') {
            throw new InvalidArgumentException('Clerk token is missing subject.');
        }

        if (isset($payload['exp']) && (int) $payload['exp'] < $now) {
            throw new InvalidArgumentException('Clerk token has expired.');
        }

        if (isset($payload['nbf']) && (int) $payload['nbf'] > $now) {
            throw new InvalidArgumentException('Clerk token is not active yet.');
        }

        $issuer = config('child_service.clerk_issuer');

        if ($issuer !== null && $issuer !== '' && ($payload['iss'] ?? null) !== $issuer) {
            throw new InvalidArgumentException('Invalid Clerk token issuer.');
        }

        $authorizedParties = config('child_service.clerk_authorized_parties', []);

        if (
            isset($payload['azp']) &&
            $authorizedParties !== [] &&
            ! in_array($payload['azp'], $authorizedParties, true)
        ) {
            throw new InvalidArgumentException('Invalid Clerk token authorized party.');
        }
    }

    private function normalizePublicKey(string $key): string
    {
        $key = str_replace('\n', "\n", trim($key));

        if (str_contains($key, 'BEGIN PUBLIC KEY')) {
            return $key;
        }

        return "-----BEGIN PUBLIC KEY-----\n"
            .chunk_split($key, 64, "\n")
            ."-----END PUBLIC KEY-----";
    }

    private function base64UrlDecode(string $value): string
    {
        $remainder = strlen($value) % 4;

        if ($remainder > 0) {
            $value .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        if ($decoded === false) {
            throw new InvalidArgumentException('Invalid base64url token segment.');
        }

        return $decoded;
    }
}
