<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class ServiceJwtService
{
    public function generateToken(
        string $serviceName,
        string $audience,
        int $ttlSeconds = 300
    ): string {

        $now = time();

        $payload = [
            'iss' => $serviceName,
            'aud' => $audience,
            'iat' => $now,
            'exp' => $now + $ttlSeconds,
        ];

        $secret = config("services.service_auth.$serviceName"); 

        return JWT::encode(
            $payload,
            $secret,
            'HS256'
        );
    }

    public function validateToken(
        string $token,
        string $expectedAudience
    ): object {

        // Decode without knowing issuer first
        $decoded = JWT::decode(
            $token,
            new Key($this->getAnySecret(), 'HS256')
        );

        if ($decoded->aud !== $expectedAudience) {
            throw new \Exception('Invalid audience');
        }

        return $decoded;
    }

    private function getAnySecret(): string
    {
        // fallback key for initial decode attempt
        return config('services.service_auth.recommendation-service');
    }
}