<?php

namespace Database\Seeders;

use App\Models\Content;
use App\Models\ContentPack;
use App\Models\ContentPackVersion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GameContentSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Create a few sample content packs each targeting a different game type
        $packs = [
            [
                'slug' => 'fidel-tracing-pack',
                'title' => 'Fidel Tracing Pack',
                'description' => 'Basic tracing activities for fidel characters',
                'game_type' => 'Fidel Tracing',
            ],
            [
                'slug' => 'voice-to-word-pack',
                'title' => 'Voice → Word Pack',
                'description' => 'Short voice prompts to map to words',
                'game_type' => 'Fidel Match',
            ],
            [
                'slug' => 'picture-to-word-pack',
                'title' => 'Picture → Word Pack',
                'description' => 'Images mapped to vocabulary',
                'game_type' => 'Pic-to-Word',
            ],
        ];

        foreach ($packs as $packData) {
            $pack = ContentPack::updateOrCreate(
                ['slug' => $packData['slug']],
                array_merge($packData, ['is_active' => true])
            );

            // Create a published version
            $payload = [
                'meta' => ['pack' => $pack->slug, 'created_at' => now()->toDateTimeString()],
                'contents' => [],
            ];

            $version = ContentPackVersion::create([
                'content_pack_id' => $pack->id,
                'version' => 1,
                'checksum' => sha1($pack->slug . ':1'),
                'size_bytes' => 0,
                'payload' => $payload,
                'published_at' => now(),
            ]);

            // simple example contents for the pack — create two content rows each
            $typesMap = [
                'Fidel Tracing' => 'fidel_tracing',
                'Fidel Match' => 'voice_to_word',
                'Pic-to-Word' => 'picture_to_word',
            ];

            $type = $typesMap[$packData['game_type']] ?? 'word_builder';

            for ($i = 1; $i <= 2; $i++) {
                Content::create([
                    'content_pack_version_id' => $version->id,
                    'type' => $type,
                    'title' => $pack->title . " — Item {$i}",
                    'description' => "Sample content item {$i} for {$pack->title}",
                    'content' => ['example' => "payload {$i}", 'items' => []],
                    'sequence_order' => $i,
                    'difficulty' => $i === 1 ? 'easy' : 'medium',
                    'is_active' => true,
                ]);
            }

            // update latest_published_version to the version number
            $pack->latest_published_version = 1;
            $pack->save();
        }
    }
}
