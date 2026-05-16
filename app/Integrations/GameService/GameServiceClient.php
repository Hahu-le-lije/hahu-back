<?php

namespace App\Integrations\GameService;

use Illuminate\Support\Facades\Http;

class GameServiceClient
{
    public function fetchEvents(?string $lastUpdated = null): array
    {
        $response = Http::withToken(
            config('services.game_service.token')
        )->get(
            config('services.game_service.url') . '/api/events',
            [
                'last_updated' => $lastUpdated,
            ]
        );

        if ($response->failed()) {
            throw new \Exception('Failed to fetch events from Game Service');
        }

        return $response->json();
    }
}
