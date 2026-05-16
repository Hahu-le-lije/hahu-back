<?php

namespace App\Support;

class ContentPayloadFormatter
{
    public static function normalize(array $payload): array
    {
        $normalized = $payload;
        $contents = self::extractContents($normalized);

        self::normalizePronunciation($contents);
        self::normalizeVoiceToWord($contents);

        $normalized['contents'] = $contents;
        $normalized['schema_version'] = $normalized['schema_version'] ?? 2;

        return $normalized;
    }

    private static function extractContents(array $payload): array
    {
        if (isset($payload['contents']) && is_array($payload['contents'])) {
            return $payload['contents'];
        }

        return $payload;
    }

    private static function normalizePronunciation(array &$contents): void
    {
        $raw = null;

        if (isset($contents['pronunciation']) && is_array($contents['pronunciation'])) {
            $raw = $contents['pronunciation'];
        } elseif (isset($contents['pronouncation']) && is_array($contents['pronouncation'])) {
            $raw = $contents['pronouncation'];
        }

        if (!is_array($raw)) {
            return;
        }

        $levels = isset($raw['levels']) && is_array($raw['levels']) ? $raw['levels'] : [];

        foreach ($levels as $levelId => $levelValue) {
            if (!is_array($levelValue)) {
                continue;
            }

            if (isset($levelValue['question1']) && is_array($levelValue['question1'])) {
                continue;
            }

            $question = [
                'word' => (string) ($levelValue['word'] ?? ''),
                'correct voice pronouncation link ' => (string) (
                    $levelValue['correct voice pronouncation link '] ??
                    $levelValue['correct voice pronouncation link'] ??
                    $levelValue['audio_url'] ??
                    ''
                ),
                'image of the word link' => (string) (
                    $levelValue['image of the word link'] ??
                    $levelValue['imageofthewordlink'] ??
                    ''
                ),
            ];

            $levels[$levelId] = ['question1' => $question];
        }

        $normalizedBlock = ['levels' => $levels];
        $contents['pronunciation'] = $normalizedBlock;
        $contents['pronouncation'] = $normalizedBlock;
    }

    private static function normalizeVoiceToWord(array &$contents): void
    {
        $raw = null;

        if (isset($contents['voice/fidel to word game']) && is_array($contents['voice/fidel to word game'])) {
            $raw = $contents['voice/fidel to word game'];
        } elseif (isset($contents['voice_to_word']) && is_array($contents['voice_to_word'])) {
            $raw = $contents['voice_to_word'];
        }

        if (!is_array($raw)) {
            return;
        }

        $levels = isset($raw['levels']) && is_array($raw['levels']) ? $raw['levels'] : [];

        foreach ($levels as $levelId => $levelValue) {
            if (!is_array($levelValue)) {
                continue;
            }

            if (isset($levelValue['question1']) && is_array($levelValue['question1'])) {
                continue;
            }

            $question = [
                'voiceof the word link' => (string) (
                    $levelValue['voiceof the word link'] ??
                    $levelValue['voiceofthewordlink'] ??
                    ''
                ),
                'word choices' => is_array($levelValue['word choices'] ?? null)
                    ? $levelValue['word choices']
                    : (is_array($levelValue['word_choices'] ?? null)
                        ? $levelValue['word_choices']
                        : []),
                'correctwordid' => (string) (
                    $levelValue['correctwordid'] ??
                    $levelValue['correct_word_id'] ??
                    ''
                ),
            ];

            $levels[$levelId] = ['question1' => $question];
        }

        $normalizedBlock = ['levels' => $levels];
        $contents['voice/fidel to word game'] = $normalizedBlock;
        $contents['voice_to_word'] = $normalizedBlock;
    }
}