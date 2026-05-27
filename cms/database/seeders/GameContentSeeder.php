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
        // Create a sample content pack for each known game type, with at least one content item
        $packs = [
            ['slug' => 'fidel-tracing-pack', 'title' => 'Fidel Tracing Pack', 'description' => 'Basic tracing activities for fidel characters', 'game_type' => 'Fidel Tracing'],
            ['slug' => 'voice-to-word-pack', 'title' => 'Voice → Word Pack', 'description' => 'Short voice prompts to map to words', 'game_type' => 'Fidel Match'],
            ['slug' => 'picture-to-word-pack', 'title' => 'Picture → Word Pack', 'description' => 'Images mapped to vocabulary', 'game_type' => 'Pic-to-Word'],
            ['slug' => 'word-builder-pack', 'title' => 'Word Builder Pack', 'description' => 'Activities to build words from letters', 'game_type' => 'Word Builder'],
            ['slug' => 'fill-in-the-blank-pack', 'title' => 'Fill In The Blank Pack', 'description' => 'Cloze exercises and fill-in-the-blank tasks', 'game_type' => 'Listen & Fill'],
            ['slug' => 'pronunciation-pack', 'title' => 'Pronunciation Pack', 'description' => 'Pronunciation practice activities', 'game_type' => 'Speak Up'],
            ['slug' => 'story-quiz-pack', 'title' => 'Story Quiz Pack', 'description' => 'Short story comprehension quizzes', 'game_type' => 'Story Quiz'],
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

            // Map game type name to content.type in the `content` table
            $typesMap = [
                'Fidel Tracing' => 'fidel_tracing',
                'Fidel Match' => 'voice_to_word',
                'Pic-to-Word' => 'picture_to_word',
                'Word Builder' => 'word_builder',
                'Listen & Fill' => 'fill_in_the_blank',
                'Speak Up' => 'pronunciation',
                'Story Quiz' => 'story_quiz',
            ];

            $type = $typesMap[$packData['game_type']] ?? 'word_builder';

            // Create a single representative content item per pack
            Content::create([
                'content_pack_version_id' => $version->id,
                'type' => $type,
                'title' => $pack->title . ' — Sample Item',
                'description' => 'Sample content for ' . $pack->title,
                'content' => ['example' => 'sample payload', 'items' => []],
                'sequence_order' => 1,
                'difficulty' => 'easy',
                'is_active' => true,
            ]);

            // update latest_published_version to the version number
            $pack->latest_published_version = 1;
            $pack->save();
        }
    }
}
