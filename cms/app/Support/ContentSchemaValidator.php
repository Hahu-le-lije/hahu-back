<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class ContentSchemaValidator
{
    public static function validateAndNormalize(string $gameType, array $payload): array
    {
        $gameType = trim($gameType);

        if ($gameType === '') {
            throw ValidationException::withMessages([
                'game_type' => 'The content pack game type is required for schema normalization.',
            ]);
        }

        $contents = self::extractContents($payload);
        $normalized = [
            'meta' => is_array($payload['meta'] ?? null) ? $payload['meta'] : [],
            'schema_version' => $payload['schema_version'] ?? 2,
        ];

        $normalized[$gameType] = match ($gameType) {
            'story_quiz' => self::normalizeStoryQuiz($contents),
            'fidel_tracing', 'pronunciation' => self::normalizeLevelGame($gameType, $contents, ['word', 'voice', 'image']),
            'voice_to_word' => self::normalizeLevelGame($gameType, $contents, ['voice', 'choices', 'correct_choice']),
            'picture_to_word' => self::normalizeLevelGame($gameType, $contents, ['image', 'choices', 'correct_choice']),
            'word_builder' => self::normalizeLevelGame($gameType, $contents, ['letters', 'target_word']),
            'fill_in_the_blank' => self::normalizeLevelGame($gameType, $contents, ['sentence', 'choices', 'correct_choice']),
            default => throw ValidationException::withMessages([
                'game_type' => 'Unsupported game type: ' . $gameType,
            ]),
        };

        return $normalized;
    }

    private static function extractContents(array $payload): array
    {
        if (isset($payload['contents']) && is_array($payload['contents'])) {
            return $payload['contents'];
        }

        return $payload;
    }

    private static function normalizeLevelGame(string $gameType, array $contents, array $requiredKeys): array
    {
        $raw = self::extractGameBlock($gameType, $contents);
        $levels = isset($raw['levels']) && is_array($raw['levels']) ? $raw['levels'] : $raw;
        $normalizedLevels = [];

        foreach ($levels as $levelId => $levelValue) {
            if (!is_array($levelValue)) {
                continue;
            }

            $normalizedQuestions = self::normalizeQuestions($levelValue, $gameType, $requiredKeys);

            if ($normalizedQuestions !== []) {
                $normalizedLevels[(string) $levelId] = $normalizedQuestions;
            }
        }

        if ($normalizedLevels === []) {
            throw ValidationException::withMessages([
                $gameType => 'At least one level with one question is required.',
            ]);
        }

        return ['levels' => $normalizedLevels];
    }

    private static function normalizeStoryQuiz(array $contents): array
    {
        $raw = self::extractGameBlock('story_quiz', $contents);
        $stories = $raw['stories'] ?? $raw;

        if (!is_array($stories) || $stories === []) {
            throw ValidationException::withMessages([
                'story_quiz' => 'At least one story is required.',
            ]);
        }

        $normalizedStories = [];

        foreach ($stories as $storyValue) {
            if (!is_array($storyValue)) {
                continue;
            }

            $questions = [];
            $sourceQuestions = is_array($storyValue['questions'] ?? null) ? $storyValue['questions'] : [];

            foreach ($sourceQuestions as $questionValue) {
                if (!is_array($questionValue)) {
                    continue;
                }

                $questions[] = [
                    'question' => (string) ($questionValue['question'] ?? ''),
                    'choices' => is_array($questionValue['choices'] ?? null) ? array_values($questionValue['choices']) : [],
                    'correct_choice' => (int) ($questionValue['correct_choice'] ?? 0),
                ];
            }

            if ($questions === []) {
                throw ValidationException::withMessages([
                    'story_quiz.questions' => 'Each story must include questions.',
                ]);
            }

            $normalizedStories[] = [
                'title' => (string) ($storyValue['title'] ?? ''),
                'pages' => is_array($storyValue['pages'] ?? null) ? array_values($storyValue['pages']) : [],
                'questions' => $questions,
            ];
        }

        if ($normalizedStories === []) {
            throw ValidationException::withMessages([
                'story_quiz' => 'At least one valid story is required.',
            ]);
        }

        return ['stories' => $normalizedStories];
    }

    private static function normalizeQuestions(array $levelValue, string $gameType, array $requiredKeys): array
    {
        if (self::looksCanonical($levelValue, $requiredKeys)) {
            $questionBlocks = [];

            foreach ($levelValue as $questionKey => $questionValue) {
                if (!is_array($questionValue)) {
                    continue;
                }

                $questionBlocks[$questionKey] = self::normalizeQuestion($gameType, $questionValue);
            }

            return $questionBlocks;
        }

        $questionBlocks = [];
        $sourceQuestions = array_values($levelValue);

        foreach ($sourceQuestions as $index => $questionValue) {
            if (!is_array($questionValue)) {
                continue;
            }

            $questionBlocks['question' . ($index + 1)] = self::normalizeQuestion($gameType, $questionValue);
        }

        return $questionBlocks;
    }

    private static function normalizeQuestion(string $gameType, array $questionValue): array
    {
        return match ($gameType) {
            'fidel_tracing', 'pronunciation' => [
                'word' => (string) ($questionValue['word'] ?? $questionValue['answer'] ?? ''),
                'voice' => (string) ($questionValue['voice'] ?? $questionValue['audio_url'] ?? $questionValue['correct voice pronouncation link '] ?? $questionValue['correct voice pronouncation link'] ?? ''),
                'image' => (string) ($questionValue['image'] ?? $questionValue['image of the word link'] ?? $questionValue['imageofthewordlink'] ?? ''),
            ],
            'voice_to_word' => [
                'voice' => (string) ($questionValue['voice'] ?? $questionValue['voiceof the word link'] ?? $questionValue['voiceofthewordlink'] ?? ''),
                'choices' => is_array($questionValue['choices'] ?? $questionValue['word choices'] ?? $questionValue['word_choices'] ?? null)
                    ? array_values($questionValue['choices'] ?? $questionValue['word choices'] ?? $questionValue['word_choices'])
                    : [],
                'correct_choice' => (int) ($questionValue['correct_choice'] ?? $questionValue['correctwordid'] ?? $questionValue['correct_word_id'] ?? 0),
            ],
            'picture_to_word' => [
                'image' => (string) ($questionValue['image'] ?? $questionValue['picture'] ?? $questionValue['image_link'] ?? ''),
                'choices' => is_array($questionValue['choices'] ?? null) ? array_values($questionValue['choices']) : [],
                'correct_choice' => (int) ($questionValue['correct_choice'] ?? $questionValue['correctwordid'] ?? $questionValue['correct_word_id'] ?? 0),
            ],
            'word_builder' => [
                'letters' => is_array($questionValue['letters'] ?? null) ? array_values($questionValue['letters']) : self::splitLetters((string) ($questionValue['letters_text'] ?? '')),
                'target_word' => (string) ($questionValue['target_word'] ?? ''),
            ],
            'fill_in_the_blank' => [
                'sentence' => (string) ($questionValue['sentence'] ?? ''),
                'choices' => is_array($questionValue['choices'] ?? null) ? array_values($questionValue['choices']) : [],
                'correct_choice' => (int) ($questionValue['correct_choice'] ?? 0),
            ],
            default => throw ValidationException::withMessages([
                'game_type' => 'Unsupported game type: ' . $gameType,
            ]),
        };
    }

    private static function extractGameBlock(string $gameType, array $contents): array
    {
        if (isset($contents[$gameType]) && is_array($contents[$gameType])) {
            return $contents[$gameType];
        }

        if ($gameType === 'voice_to_word' && isset($contents['voice/fidel to word game']) && is_array($contents['voice/fidel to word game'])) {
            return $contents['voice/fidel to word game'];
        }

        if ($gameType === 'pronunciation' && isset($contents['pronouncation']) && is_array($contents['pronouncation'])) {
            return $contents['pronouncation'];
        }

        return $contents;
    }

    private static function looksCanonical(array $levelValue, array $requiredKeys): bool
    {
        foreach ($levelValue as $questionValue) {
            if (!is_array($questionValue)) {
                return false;
            }

            if (count(array_intersect($requiredKeys, array_keys($questionValue))) === 0) {
                return false;
            }
        }

        return $levelValue !== [];
    }

    private static function splitLetters(string $word): array
    {
        if ($word === '') {
            return [];
        }

        return preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }
}