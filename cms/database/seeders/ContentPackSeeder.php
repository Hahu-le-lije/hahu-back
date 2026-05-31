<?php

namespace Database\Seeders;

use App\Models\ContentPack;
use App\Models\ContentPackVersion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContentPackSeeder extends Seeder
{
    /**
     * Seed canonical content packs for the child app.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->packs() as $packDefinition) {
                $pack = ContentPack::updateOrCreate(
                    ['slug' => $packDefinition['slug']],
                    [
                        'title' => $packDefinition['title'],
                        'description' => $packDefinition['description'],
                        'game_type' => $packDefinition['game_type'],
                        'thumbnail_url' => $packDefinition['thumbnail_url'],
                        'size_mb' => $packDefinition['size_mb'],
                        'is_active' => true,
                    ]
                );

                $payload = $packDefinition['payload'];
                $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                $checksum = hash('sha256', $payloadJson);

                $version = ContentPackVersion::updateOrCreate(
                    [
                        'content_pack_id' => $pack->id,
                        'version' => 1,
                    ],
                    [
                        'checksum' => $checksum,
                        'size_bytes' => strlen($payloadJson),
                        'payload' => $payload,
                        'min_app_version' => $packDefinition['min_app_version'],
                        'published_at' => now(),
                    ]
                );

                $pack->update([
                    'latest_published_version' => $version->version,
                ]);
            }
        });
    }

    /**
     * Return canonical pack definitions.
     *
     * @return array<int, array<string, mixed>>
     */
    private function packs(): array
    {
        return [
            [
                'slug' => 'fidel-tracing-pack',
                'title' => 'Fidel Tracing Pack',
                'description' => 'Sample canonical pack for fidel tracing.',
                'game_type' => 'fidel_tracing',
                'thumbnail_url' => null,
                'size_mb' => 1,
                'min_app_version' => '1.0.0',
                'payload' => [
                    'meta' => [
                        'pack' => 'fidel-tracing-pack',
                        'created_at' => '2026-05-23T15:01:55Z',
                    ],
                    'fidel_tracing' => [
                        'levels' => [
                            '1' => [
                                'question1' => [
                                    'word' => 'ሀ',
                                    'voice' => 'https://cdn.example.com/audio/ha.mp3',
                                    'image' => 'https://cdn.example.com/images/ha.png',
                                ],
                            ],
                            '2' => [
                                'question1' => [
                                    'word' => 'ሁ',
                                    'voice' => 'https://cdn.example.com/audio/hu.mp3',
                                    'image' => 'https://cdn.example.com/images/hu.png',
                                ],
                            ],
                        ],
                    ],
                    'schema_version' => 2,
                ],
            ],
            [
                'slug' => 'voice-to-word-pack',
                'title' => 'Voice to Word Pack',
                'description' => 'Sample canonical pack for voice/fidel to word.',
                'game_type' => 'voice_to_word',
                'thumbnail_url' => null,
                'size_mb' => 1,
                'min_app_version' => '1.0.0',
                'payload' => [
                    'meta' => [
                        'pack' => 'voice-to-word-pack',
                        'created_at' => '2026-05-23T15:01:55Z',
                    ],
                    'voice_to_word' => [
                        'levels' => [
                            '1' => [
                                'question1' => [
                                    'voice' => 'https://cdn.example.com/audio/word1.mp3',
                                    'choices' => ['ሀ', 'ሁ', 'ሂ'],
                                    'correct_choice' => 0,
                                ],
                            ],
                        ],
                    ],
                    'schema_version' => 2,
                ],
            ],
            [
                'slug' => 'picture-to-word-pack',
                'title' => 'Picture to Word Pack',
                'description' => 'Sample canonical pack for picture to word.',
                'game_type' => 'picture_to_word',
                'thumbnail_url' => null,
                'size_mb' => 1,
                'min_app_version' => '1.0.0',
                'payload' => [
                    'meta' => [
                        'pack' => 'picture-to-word-pack',
                        'created_at' => '2026-05-23T15:01:55Z',
                    ],
                    'picture_to_word' => [
                        'levels' => [
                            '1' => [
                                'question1' => [
                                    'image' => 'https://cdn.example.com/images/apple.png',
                                    'choices' => ['ፖም', 'ሀ', 'ቦ'],
                                    'correct_choice' => 0,
                                ],
                            ],
                        ],
                    ],
                    'schema_version' => 2,
                ],
            ],
            [
                'slug' => 'word-builder-pack',
                'title' => 'Word Builder Pack',
                'description' => 'Sample canonical pack for word builder.',
                'game_type' => 'word_builder',
                'thumbnail_url' => null,
                'size_mb' => 1,
                'min_app_version' => '1.0.0',
                'payload' => [
                    'meta' => [
                        'pack' => 'word-builder-pack',
                        'created_at' => '2026-05-23T15:01:55Z',
                    ],
                    'word_builder' => [
                        'levels' => [
                            '1' => [
                                'question1' => [
                                    'letters' => ['ሀ', 'ሁ', 'ሂ'],
                                    'target_word' => 'ሀሁ',
                                ],
                            ],
                        ],
                    ],
                    'schema_version' => 2,
                ],
            ],
            [
                'slug' => 'fill-in-the-blank-pack',
                'title' => 'Fill in the Blank Pack',
                'description' => 'Sample canonical pack for fill in the blank.',
                'game_type' => 'fill_in_the_blank',
                'thumbnail_url' => null,
                'size_mb' => 1,
                'min_app_version' => '1.0.0',
                'payload' => [
                    'meta' => [
                        'pack' => 'fill-in-the-blank-pack',
                        'created_at' => '2026-05-23T15:01:55Z',
                    ],
                    'fill_in_the_blank' => [
                        'levels' => [
                            '1' => [
                                'question1' => [
                                    'sentence' => 'እኔ ____ ነኝ',
                                    'choices' => ['ልጅ', 'አባት', 'እናት'],
                                    'correct_choice' => 0,
                                ],
                            ],
                        ],
                    ],
                    'schema_version' => 2,
                ],
            ],
            [
                'slug' => 'pronunciation-pack',
                'title' => 'Pronunciation Pack',
                'description' => 'Sample canonical pack for pronunciation.',
                'game_type' => 'pronunciation',
                'thumbnail_url' => null,
                'size_mb' => 1,
                'min_app_version' => '1.0.0',
                'payload' => [
                    'meta' => [
                        'pack' => 'pronunciation-pack',
                        'created_at' => '2026-05-23T15:01:55Z',
                    ],
                    'pronunciation' => [
                        'levels' => [
                            '1' => [
                                'question1' => [
                                    'word' => 'ሀ',
                                    'voice' => 'https://cdn.example.com/audio/ha.mp3',
                                    'image' => 'https://cdn.example.com/images/ha.png',
                                ],
                            ],
                        ],
                    ],
                    'schema_version' => 2,
                ],
            ],
            [
                'slug' => 'story-quiz-pack',
                'title' => 'Story Quiz Pack',
                'description' => 'Sample canonical pack for story quiz.',
                'game_type' => 'story_quiz',
                'thumbnail_url' => null,
                'size_mb' => 1,
                'min_app_version' => '1.0.0',
                'payload' => [
                    'meta' => [
                        'pack' => 'story-quiz-pack',
                        'created_at' => '2026-05-23T15:01:55Z',
                    ],
                    'story_quiz' => [
                        'stories' => [
                            [
                                'title' => 'የአንበሳው ታሪክ',
                                'pages' => [
                                    [
                                        'text' => 'አንበሳው በዱር ይኖራል...',
                                        'image' => 'https://cdn.example.com/images/lion.png',
                                    ],
                                ],
                                'questions' => [
                                    [
                                        'question' => 'አንበሳው የት ይኖራል?',
                                        'choices' => ['በዱር', 'በከተማ', 'በቤት'],
                                        'correct_choice' => 0,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'schema_version' => 2,
                ],
            ],
        ];
    }
}