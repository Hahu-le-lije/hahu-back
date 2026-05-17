<?php

namespace App\Services;

use App\Jobs\ProcessLearningEventJob;
use App\Models\LearningEvent;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class LearningSessionIngestionService
{
    public function ingest(array $sessions): array
    {
        $accepted = 0;
        $created = 0;
        $duplicates = 0;
        $eventIds = [];
        $errors = [];

        foreach ($sessions as $index => $session) {
            if (! is_array($session)) {
                $errors["sessions.{$index}"][] = 'Session must be an object.';
                continue;
            }

            try {
                $payload = $this->normalize($session);
            } catch (ValidationException $exception) {
                foreach ($exception->errors() as $field => $messages) {
                    $errors["sessions.{$index}.{$field}"] = $messages;
                }

                continue;
            }

            $learningEvent = DB::transaction(function () use ($payload) {
                return LearningEvent::query()->firstOrCreate(
                    ['event_id' => $payload['event_id']],
                    $payload
                );
            });

            $accepted++;
            $eventIds[] = $learningEvent->event_id;

            if ($learningEvent->wasRecentlyCreated) {
                $created++;
                ProcessLearningEventJob::dispatch($learningEvent);
            } else {
                $duplicates++;
            }
        }

        if ($accepted === 0 && $errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'accepted' => $accepted,
            'created' => $created,
            'duplicates' => $duplicates,
            'event_ids' => $eventIds,
            'errors' => $errors,
        ];
    }

    private function normalize(array $session): array
    {
        $eventId = $this->stringValue($session, ['event_id', 'eventId', 'session_id', 'sessionId', 'id']);
        $childId = $this->stringValue($session, ['child_id', 'childId']);
        $gameType = $this->stringValue($session, ['game_type', 'gameType', 'type']);
        $contentId = $this->stringValue($session, ['content_id', 'contentId', 'lesson_id', 'lessonId']);

        $errors = [];

        foreach ([
            'event_id' => $eventId,
            'child_id' => $childId,
            'game_type' => $gameType,
            'content_id' => $contentId,
        ] as $field => $value) {
            if ($value === null) {
                $errors[$field][] = "The {$field} field is required.";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'event_id' => $eventId,
            'child_id' => $childId,
            'game_type' => $gameType,
            'content_id' => $contentId,
            'score' => $this->integerValue($session, ['score'], 0),
            'time_spent' => $this->timeSpent($session),
            'metrics' => $this->metrics($session),
            'skill_breakdown' => $this->arrayValue($session, ['skill_breakdown', 'skillBreakdown', 'skills'], []),
            'event_created_at' => $this->dateValue($session, [
                'event_created_at',
                'eventCreatedAt',
                'created_at',
                'createdAt',
                'completed_at',
                'completedAt',
                'ended_at',
                'endedAt',
                'timestamp',
            ]) ?? now(),
            'last_updated' => $this->dateValue($session, [
                'last_updated',
                'lastUpdated',
                'updated_at',
                'updatedAt',
            ]) ?? now(),
            'synced_at' => now(),
        ];
    }

    private function metrics(array $session): array
    {
        $metrics = $this->arrayValue($session, ['metrics'], []);

        foreach ([
            'total_questions' => ['total_questions', 'totalQuestions'],
            'correct_answers' => ['correct_answers', 'correctAnswers'],
        ] as $target => $keys) {
            $value = $this->nullableIntegerValue($session, $keys);

            if ($value !== null) {
                $metrics[$target] = $value;
            }
        }

        return $metrics;
    }

    private function timeSpent(array $session): int
    {
        $seconds = $this->nullableIntegerValue($session, [
            'time_spent',
            'timeSpent',
            'duration_seconds',
            'durationSeconds',
        ]);

        if ($seconds !== null) {
            return max(0, $seconds);
        }

        $milliseconds = $this->nullableIntegerValue($session, ['duration_ms', 'durationMs']);

        if ($milliseconds !== null) {
            return max(0, (int) round($milliseconds / 1000));
        }

        return 0;
    }

    private function stringValue(array $source, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = Arr::get($source, $key);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function integerValue(array $source, array $keys, int $default): int
    {
        return $this->nullableIntegerValue($source, $keys) ?? $default;
    }

    private function nullableIntegerValue(array $source, array $keys): ?int
    {
        foreach ($keys as $key) {
            $value = Arr::get($source, $key);

            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }

    private function arrayValue(array $source, array $keys, array $default): array
    {
        foreach ($keys as $key) {
            $value = Arr::get($source, $key);

            if (is_array($value)) {
                return $value;
            }

            if (is_string($value) && $value !== '') {
                $decoded = json_decode($value, true);

                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        return $default;
    }

    private function dateValue(array $source, array $keys): ?Carbon
    {
        foreach ($keys as $key) {
            $value = Arr::get($source, $key);

            if ($value === null || $value === '') {
                continue;
            }

            try {
                return Carbon::parse($value);
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }
}
