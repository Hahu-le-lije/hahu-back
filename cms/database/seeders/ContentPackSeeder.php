<?php

namespace Database\Seeders;

use App\Models\ContentPack;
use Illuminate\Database\Seeder;

class ContentPackSeeder extends Seeder
{
    public function run(): void
    {
        $payload = [
            'levels' => [
                [
                    'id' => 'tracing_1',
                    'game_type' => 'tracing',
                    'level_number' => 1,
                    'title' => 'Basic Fidels',
                    'description' => 'Learn to trace simple Amharic letters',
                    'difficulty' => 1,
                    'unlocked_at_start' => 1,
                    'required_score' => 0,
                ],
            ],
            'fidels' => [
                [
                    'id' => 'f1',
                    'character' => '\u1200',
                    'pronunciation' => 'ha',
                    'audio_url' => null,
                    'difficulty_level' => 1,
                    'level_id' => 'tracing_1',
                    'stroke_order' => '[{"x":10,"y":10},{"x":20,"y":20}]',
                ],
            ],
            'words' => [],
            'word_images' => [],
            'sentences' => [],
            'sentence_words' => [],
            'fill_blank_exercises' => [],
        ];

        $pack = ContentPack::query()->updateOrCreate(
            [
                'slug' => 'fidel_tracing_pack',
            ],
            [
                'title' => 'Fidel Tracing Pack',
                'description' => 'Starter tracing pack for Phase 1 CMS',
                'game_type' => 'tracing',
                'thumbnail_url' => null,
                'size_mb' => 1,
                'is_active' => true,
            ]
        );

        $pack->versions()->updateOrCreate(
            [
                'version' => 1,
            ],
            [
                'checksum' => 'abc123',
                'size_bytes' => 1024,
                'payload' => $payload,
                'min_app_version' => '1.0.0',
                'published_at' => now(),
            ]
        );
    }
}
