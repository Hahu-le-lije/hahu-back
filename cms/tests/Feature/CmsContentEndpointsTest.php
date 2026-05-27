<?php

namespace Tests\Feature;

use App\Models\ContentPack;
use App\Models\ContentPackVersion;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsContentEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_endpoints_accept_shared_jwt_and_return_pack_data(): void
    {
        config(['auth.jwt_secret' => 'testing-shared-secret']);

        $pack = ContentPack::create([
            'slug' => 'alphabets-basic',
            'title' => 'Alphabets Basic',
            'description' => 'Starter pack for alphabet practice.',
            'game_type' => 'fidel_tracing',
            'thumbnail_url' => 'https://example.com/thumb.png',
            'size_mb' => 42,
            'is_active' => true,
            'latest_published_version' => 1,
        ]);

        ContentPackVersion::create([
            'content_pack_id' => $pack->id,
            'version' => 1,
            'checksum' => str_repeat('a', 64),
            'size_bytes' => 1024,
            'payload' => [
                'files' => [
                    ['path' => 'manifest.json', 'sha256' => str_repeat('b', 64)],
                ],
            ],
            'min_app_version' => '1.0.0',
            'published_at' => now(),
        ]);

        $token = (new JwtService())->issue([
            'sub' => 'child-123',
            'parent_id' => 'parent-456',
            'role' => 'child',
            'aud' => 'cms',
            'scope' => 'content:read',
        ], 'testing-shared-secret', 60);

        $this->withToken($token)
            ->getJson('/api/content/packs')
            ->assertOk()
            ->assertJsonPath('contentPacks.0.id', 'alphabets-basic')
            ->assertJsonPath('contentPacks.0.version', 1);

        $this->withToken($token)
            ->getJson('/api/content/packs/alphabets-basic/manifest')
            ->assertOk()
            ->assertJsonPath('id', 'alphabets-basic')
            ->assertJsonPath('version', 1)
            ->assertJsonPath('downloadUrl', route('content.packs.download', ['slug' => 'alphabets-basic']));

        $this->withToken($token)
            ->getJson('/api/content/packs/alphabets-basic/download')
            ->assertOk()
            ->assertJsonPath('files.0.path', 'manifest.json')
            ->assertJsonPath('files.0.sha256', str_repeat('b', 64));
    }
}
