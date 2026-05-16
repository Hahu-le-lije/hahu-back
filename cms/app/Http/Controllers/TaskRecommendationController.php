<?php

namespace App\Http\Controllers;

use App\Models\AssignedTask;
use App\Models\Content;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TaskRecommendationController extends Controller
{
    // GET /api/children/{child_id}/tasks/recommendations
    public function recommendations(string $childId): JsonResponse
    {
        $recommendations = [];

        // Prefer receiving summaries from the external sync service
        $syncUrl = config('services.sync.url') ?? env('SYNC_SERVICE_URL');
        if ($syncUrl) {
            try {
                $resp = Http::timeout(5)->get(rtrim($syncUrl, '/') . "/api/children/{$childId}/summaries/latest");
                if ($resp->successful()) {
                    $data = $resp->json();
                    // Expecting data to be an array of { game_type_id, mastery_score, accuracy }
                    foreach ($data as $row) {
                        $gameTypeId = (int)($row['game_type_id'] ?? 0);
                        $mastery = isset($row['mastery_score']) ? (float)$row['mastery_score'] : null;
                        $accuracy = isset($row['accuracy']) ? (float)$row['accuracy'] : null;

                        if ($this->needsRecommendation($mastery, $accuracy)) {
                            $content = $this->findContentForGameType($gameTypeId);
                            if ($content) {
                                $reason = $mastery !== null && $mastery < 0.7
                                    ? "Mastery score below 0.7"
                                    : ( ($accuracy !== null && $accuracy < 0.7) ? "Accuracy below 0.7" : "Performance recommendation");

                                $recommendations[] = [
                                    'game_type_id' => $gameTypeId,
                                    'content_id' => $content->id,
                                    'title' => $content->title,
                                    'reason' => $reason,
                                ];
                            }
                        }
                    }
                    return response()->json(['recommendations' => $recommendations]);
                }
            } catch (\Throwable $e) {
                Log::warning('Sync service unavailable for recommendations: ' . $e->getMessage());
            }
        }

        // Fallback: derive simple heuristics from local learning_events or fall back to content list
        // We attempt to read aggregated metrics from daily_summaries if present, else we return a minimal list.
        if (Schema::hasTable('daily_summaries')) {
            $rows = DB::table('daily_summaries')
                ->where('child_id', $childId)
                ->orderByDesc('summary_date')
                ->get();

            $grouped = $rows->groupBy('game_type');
            foreach ($grouped as $gameType => $rowsForType) {
                $latest = $rowsForType->first();
                $mastery = (float)($latest->mastery_score ?? 1);
                $accuracy = (float)($latest->accuracy ?? 1);
                if ($this->needsRecommendation($mastery, $accuracy)) {
                    $content = Content::active()->ofType($gameType)->ordered()->first();
                    if ($content) {
                        $recommendations[] = [
                            'game_type_id' => $this->gameTypeId($gameType),
                            'content_id' => $content->id,
                            'title' => $content->title,
                            'reason' => $mastery < 0.7 ? 'Mastery score below 0.7' : 'Accuracy below 0.7',
                        ];
                    }
                }
            }
            return response()->json(['recommendations' => $recommendations]);
        }

        // Final fallback: return top active content across known game types
        foreach (range(1, 7) as $gt) {
            $content = $this->findContentForGameType($gt);
            if ($content) {
                $recommendations[] = [
                    'game_type_id' => $gt,
                    'content_id' => $content->id,
                    'title' => $content->title,
                    'reason' => 'Suggested content (no sync data available)',
                ];
            }
        }

        return response()->json(['recommendations' => $recommendations]);
    }

    // POST /api/children/{child_id}/tasks/assign
    public function assign(Request $request, string $childId): JsonResponse
    {
        $validated = $request->validate([
            'content_id' => 'required|integer|exists:content,id',
            'game_type_id' => 'required|integer',
            'reason' => 'nullable|string',
        ]);
        $task = AssignedTask::create([
            'child_id' => $childId,
            'content_id' => $validated['content_id'],
            'game_type_id' => $validated['game_type_id'],
            'status' => 'pending',
            'assigned_by' => $request->user()?->id ?? null,
            'assigned_at' => now(),
            'reason' => $validated['reason'] ?? null,
        ]);
        return response()->json(['success' => true, 'assigned_task' => $task]);
    }

    private function gameTypeId($gameType): int
    {
        // Map string game_type to numeric ID as per your GAME_TYPES
        $map = [
            'Fidel Tracing' => 1,
            'Fidel Match' => 2,
            'Pic-to-Word' => 3,
            'Word Builder' => 4,
            'Listen & Fill' => 5,
            'Speak Up' => 6,
            'Story Quiz' => 7,
        ];
        return $map[$gameType] ?? 0;
    }

    private function needsRecommendation(?float $mastery, ?float $accuracy): bool
    {
        if ($mastery !== null && $mastery < 0.7) return true;
        if ($accuracy !== null && $accuracy < 0.7) return true;
        return false;
    }

    private function findContentForGameType(int $gameTypeId)
    {
        $type = $this->mapGameTypeToContentType($gameTypeId);
        if (!$type) return null;
        return Content::active()->ofType($type)->ordered()->first();
    }

    private function mapGameTypeToContentType(int $gameTypeId): ?string
    {
        // Map numeric game_type_id to the content.type values used in the content table
        $map = [
            1 => 'fidel_tracing',
            2 => 'voice_to_word',
            3 => 'picture_to_word',
            4 => 'word_builder',
            5 => 'fill_in_the_blank',
            6 => 'pronunciation',
            7 => 'story_quiz',
        ];
        return $map[$gameTypeId] ?? null;
    }
}
