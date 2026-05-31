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
        $createdAt = '2026-05-23T15:01:55Z';

        return [
            $this->makeTracingPack($createdAt),
            $this->makeVoiceToWordPack($createdAt),
            $this->makePictureToWordPack($createdAt),
            $this->makeWordBuilderPack($createdAt),
            $this->makeFillInTheBlankPack($createdAt),
            $this->makePronunciationPack($createdAt),
            $this->makeStoryQuizPack($createdAt),
        ];
    }

    private function makeTracingPack(string $createdAt): array
    {
        return [
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
                    'created_at' => $createdAt,
                ],
                'fidel_tracing' => [
                    'levels' => $this->makeFiveLevels(function (int $level, int $question): array {
                        $lettersByLevel = [
                            1 => ['ሀ', 'ሁ', 'ሂ', 'ሃ', 'ሄ'],
                            2 => ['ለ', 'ሉ', 'ሊ', 'ላ', 'ሌ'],
                            3 => ['ሐ', 'ሑ', 'ሒ', 'ሓ', 'ሔ'],
                            4 => ['መ', 'ሙ', 'ሚ', 'ማ', 'ሜ'],
                            5 => ['ሠ', 'ሡ', 'ሢ', 'ሣ', 'ሤ'],
                        ];

                        $letter = $lettersByLevel[$level][$question - 1];

                        return [
                            'word' => $letter,
                            'voice' => "https://cdn.example.com/audio/fidel/{$level}-{$question}.mp3",
                            'image' => "https://cdn.example.com/images/fidel/{$level}-{$question}.png",
                        ];
                    }),
                ],
                'schema_version' => 2,
            ],
        ];
    }

    private function makeVoiceToWordPack(string $createdAt): array
    {
        return [
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
                    'created_at' => $createdAt,
                ],
                'voice_to_word' => [
                    'levels' => $this->makeFiveLevels(function (int $level, int $question): array {
                        $choicesByLevel = [
                            1 => ['ሀ', 'ሁ', 'ሂ', 'ሃ', 'ሄ'],
                            2 => ['ለ', 'ሉ', 'ሊ', 'ላ', 'ሌ'],
                            3 => ['ሐ', 'ሑ', 'ሒ', 'ሓ', 'ሔ'],
                            4 => ['መ', 'ሙ', 'ሚ', 'ማ', 'ሜ'],
                            5 => ['ሠ', 'ሡ', 'ሢ', 'ሣ', 'ሤ'],
                        ];

                        return [
                            'voice' => "https://cdn.example.com/audio/voice-to-word/{$level}-{$question}.mp3",
                            'choices' => $choicesByLevel[$level],
                            'correct_choice' => 0,
                        ];
                    }),
                ],
                'schema_version' => 2,
            ],
        ];
    }

    private function makePictureToWordPack(string $createdAt): array
    {
        return [
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
                    'created_at' => $createdAt,
                ],
                'picture_to_word' => [
                    'levels' => $this->makeFiveLevels(function (int $level, int $question): array {
                        $images = [
                            1 => ['apple', 'banana', 'cup', 'book', 'ball'],
                            2 => ['dog', 'cat', 'bird', 'fish', 'horse'],
                            3 => ['tree', 'flower', 'cloud', 'sun', 'moon'],
                            4 => ['chair', 'table', 'door', 'window', 'lamp'],
                            5 => ['car', 'bus', 'bike', 'train', 'plane'],
                        ];

                        $subject = $images[$level][$question - 1];
                        $options = [$this->labelForImage($subject), 'ሀ', 'ሁ', 'ሂ'];

                        return [
                            'image' => "https://cdn.example.com/images/picture-to-word/{$subject}.png",
                            'choices' => $options,
                            'correct_choice' => 0,
                        ];
                    }),
                ],
                'schema_version' => 2,
            ],
        ];
    }

    private function makeWordBuilderPack(string $createdAt): array
    {
        return [
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
                    'created_at' => $createdAt,
                ],
                'word_builder' => [
                    'levels' => $this->makeFiveLevels(function (int $level, int $question): array {
                        $targetWords = [
                            1 => ['ሀሁ', 'ሀሂ', 'ሀሃ', 'ሀሄ', 'ሀለ'],
                            2 => ['ለሉ', 'ለሊ', 'ለላ', 'ለሌ', 'ለመ'],
                            3 => ['ሐሑ', 'ሐሒ', 'ሐሓ', 'ሐሔ', 'ሐመ'],
                            4 => ['መሙ', 'መሚ', 'መማ', 'መሜ', 'መሠ'],
                            5 => ['ሠሡ', 'ሠሢ', 'ሠሣ', 'ሠሤ', 'ሠሀ'],
                        ];

                        $word = $targetWords[$level][$question - 1];

                        return [
                            'letters' => mb_str_split($word),
                            'target_word' => $word,
                        ];
                    }),
                ],
                'schema_version' => 2,
            ],
        ];
    }

    private function makeFillInTheBlankPack(string $createdAt): array
    {
        return [
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
                    'created_at' => $createdAt,
                ],
                'fill_in_the_blank' => [
                    'levels' => $this->makeFiveLevels(function (int $level, int $question): array {
                        $sentences = [
                            1 => ['እኔ ____ ነኝ', 'አንተ ____ ነህ', 'እሷ ____ ናት', 'እኛ ____ ነን', 'እነሱ ____ ናቸው'],
                            2 => ['ዛሬ ____ ነው', 'እኛ ____ እንማራለን', 'ቤቱ ____ ነው', 'መጽሐፉ ____ ነው', 'ወተቱ ____ ነው'],
                            3 => ['____ እባብ ነው', '____ ውሻ ነው', '____ ወፍ ነው', '____ አበባ ነው', '____ ፀሐይ ነው'],
                            4 => ['ይህ ____ ትልቅ ነው', 'ያ ____ ትንሽ ነው', '____ ቀለም አለው', '____ ውሃ ነው', '____ አበባ ነው'],
                            5 => ['ልጁ ____ ይጫወታል', 'ተማሪው ____ ያነባል', 'እናቱ ____ ታበስላለች', 'አባቱ ____ ይሰራል', 'እህቱ ____ ትዘፍናለች'],
                        ];

                        $choices = ['ልጅ', 'አባት', 'እናት', 'ጓደኛ'];

                        return [
                            'sentence' => $sentences[$level][$question - 1],
                            'choices' => $choices,
                            'correct_choice' => 0,
                        ];
                    }),
                ],
                'schema_version' => 2,
            ],
        ];
    }

    private function makePronunciationPack(string $createdAt): array
    {
        return [
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
                    'created_at' => $createdAt,
                ],
                'pronunciation' => [
                    'levels' => $this->makeFiveLevels(function (int $level, int $question): array {
                        $words = [
                            1 => ['ሀ', 'ሁ', 'ሂ', 'ሃ', 'ሄ'],
                            2 => ['ለ', 'ሉ', 'ሊ', 'ላ', 'ሌ'],
                            3 => ['ሐ', 'ሑ', 'ሒ', 'ሓ', 'ሔ'],
                            4 => ['መ', 'ሙ', 'ሚ', 'ማ', 'ሜ'],
                            5 => ['ሠ', 'ሡ', 'ሢ', 'ሣ', 'ሤ'],
                        ];

                        $word = $words[$level][$question - 1];

                        return [
                            'word' => $word,
                            'voice' => "https://cdn.example.com/audio/pronunciation/{$level}-{$question}.mp3",
                            'image' => "https://cdn.example.com/images/pronunciation/{$level}-{$question}.png",
                        ];
                    }),
                ],
                'schema_version' => 2,
            ],
        ];
    }

    private function makeStoryQuizPack(string $createdAt): array
    {
        return [
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
                    'created_at' => $createdAt,
                ],
                'story_quiz' => [
                    'stories' => $this->makeFiveStories(),
                ],
                'schema_version' => 2,
            ],
        ];
    }

    /**
     * Build five levels with five questions/items each.
     *
     * @param callable(int,int): array<string, mixed> $questionFactory
     * @return array<string, array<string, array<string, mixed>>>
     */
    private function makeFiveLevels(callable $questionFactory): array
    {
        $levels = [];

        for ($level = 1; $level <= 5; $level++) {
            $questions = [];

            for ($question = 1; $question <= 5; $question++) {
                $questions['question' . $question] = $questionFactory($level, $question);
            }

            $levels[(string) $level] = $questions;
        }

        return $levels;
    }

    /**
     * Build five stories with five questions each.
     *
     * @return array<int, array<string, mixed>>
     */
    private function makeFiveStories(): array
    {
        $storyTopics = [
            'የአንበሳው ታሪክ',
            'የጥንቸሉ ጀብድ',
            'የወፉ ጉዞ',
            'የእንቁላሉ ቀን',
            'የብርሃኑ ምሳሌ',
        ];

        $stories = [];

        foreach ($storyTopics as $index => $title) {
            $storyNumber = $index + 1;
            $stories[] = [
                'title' => $title,
                'pages' => [
                    [
                        'text' => 'በታሪኩ ውስጥ የተለያዩ ሁኔታዎች ይታያሉ።',
                        'image' => "https://cdn.example.com/images/story-quiz/story-{$storyNumber}-cover.png",
                    ],
                ],
                'questions' => $this->makeStoryQuestions($storyNumber, $title),
            ];
        }

        return $stories;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function makeStoryQuestions(int $storyNumber, string $storyTitle): array
    {
        $questions = [];

        for ($question = 1; $question <= 5; $question++) {
            $questions[] = [
                'question' => $storyTitle . ' - ጥያቄ ' . $question,
                'choices' => ['አ', 'በ', 'ገ', 'ደ'],
                'correct_choice' => 0,
            ];
        }

        return $questions;
    }

    private function labelForImage(string $subject): string
    {
        return match ($subject) {
            'apple' => 'ፖም',
            'banana' => 'ሙዝ',
            'cup' => 'ጽዋ',
            'book' => 'መጽሐፍ',
            'ball' => 'ኳስ',
            'dog' => 'ውሻ',
            'cat' => 'ድመት',
            'bird' => 'ወፍ',
            'fish' => 'ዓሣ',
            'horse' => 'ፈረስ',
            'tree' => 'ዛፍ',
            'flower' => 'አበባ',
            'cloud' => 'ደመና',
            'sun' => 'ፀሐይ',
            'moon' => 'ጨረቃ',
            'chair' => 'ወንበር',
            'table' => 'ጠረጴዛ',
            'door' => 'በር',
            'window' => 'መስኮት',
            'lamp' => 'መብራት',
            'car' => 'መኪና',
            'bus' => 'አውቶቡስ',
            'bike' => 'ብስክሌት',
            'train' => 'ባቡር',
            'plane' => 'አውሮፕላን',
            default => 'ምስል',
        };
    }
}