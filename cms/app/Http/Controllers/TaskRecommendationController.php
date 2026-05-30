<?php

namespace App\Http\Controllers;

use App\Models\AssignedTask;
use App\Models\Content;
use App\Services\ChildServiceClient;
use App\Services\SyncServiceClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TaskRecommendationController extends Controller
{
    protected ChildServiceClient $childServiceClient;
    protected SyncServiceClient $syncServiceClient;

    public function __construct(
        ChildServiceClient $childServiceClient,
        SyncServiceClient $syncServiceClient
    ) {
        $this->childServiceClient = $childServiceClient;
        $this->syncServiceClient = $syncServiceClient;
    }

    /**
     * Get task recommendations for a child
     * GET /api/children/{child_id}/tasks/recommendations
     */
    public function recommendations(Request $request, string $childId): JsonResponse
    {
        try {
            $parent = $request->attributes->get('parent');
            $parentId = $parent?->clerk_id;

            if (!$parentId) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Verify parent owns this child
            if (!$this->childServiceClient->verifyParentOwnsChild($childId, $parentId)) {
                Log::warning("Unauthorized child access attempt: parent {$parentId} tried to access child {$childId}");
                return response()->json(['error' => 'Child not found or not owned by parent'], 403);
            }

            $recommendations = $this->generateRecommendations($childId);

            return response()->json(['recommendations' => $recommendations]);
        } catch (\Throwable $e) {
            Log::error("Error getting recommendations: " . $e->getMessage());
            return response()->json(['error' => 'Failed to get recommendations'], 500);
        }
    }

    /**
     * Assign a task to a child
     * POST /api/children/{child_id}/tasks/assign
     */
    public function assign(Request $request, string $childId): JsonResponse
    {
        try {
            $parent = $request->attributes->get('parent');
            $parentId = $parent?->clerk_id;

            if (!$parentId) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Verify parent owns this child
            if (!$this->childServiceClient->verifyParentOwnsChild($childId, $parentId)) {
                Log::warning("Unauthorized task assignment attempt: parent {$parentId} tried to assign to child {$childId}");
                return response()->json(['error' => 'Child not found or not owned by parent'], 403);
            }

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
                'assigned_by' => $parent->id,
                'assigned_at' => now(),
                'reason' => $validated['reason'] ?? null,
            ]);

            // Notify other services about the task assignment
            $this->notifyServicesOfTaskAssignment($task, $childId);

            return response()->json(['success' => true, 'assigned_task' => $task], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Throwable $e) {
            Log::error("Error assigning task: " . $e->getMessage());
            return response()->json(['error' => 'Failed to assign task'], 500);
        }
    }

    /**
     * Generate recommendations based on sync service data
     */
    private function generateRecommendations(string $childId): array
    {
        $recommendations = [];

        // Try to get summaries from sync service (preferred method)
        $summaries = $this->syncServiceClient->getLatestSummaries($childId);

        if ($summaries && is_array($summaries)) {
            foreach ($summaries as $row) {
                $gameTypeId = (int)($row['game_type_id'] ?? 0);
                $mastery = isset($row['mastery_score']) ? (float)$row['mastery_score'] : null;
                $accuracy = isset($row['accuracy']) ? (float)$row['accuracy'] : null;

                if ($this->needsRecommendation($mastery, $accuracy)) {
                    $content = $this->findContentForGameType($gameTypeId);
                    if ($content) {
                        $reason = $mastery !== null && $mastery < 0.7
                            ? "Mastery score below 0.7"
                            : (($accuracy !== null && $accuracy < 0.7) ? "Accuracy below 0.7" : "Performance recommendation");

                        $recommendations[] = [
                            'game_type_id' => $gameTypeId,
                            'content_id' => $content->id,
                            'title' => $content->title,
                            'reason' => $reason,
                        ];
                    }
                }
            }
            return $recommendations;
        }

        // Fallback: Try local daily_summaries table if present
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
            return $recommendations;
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

        return $recommendations;
    }

    /**
     * Notify other services about task assignment
     */
    private function notifyServicesOfTaskAssignment(AssignedTask $task, string $childId): void
    {
        try {
            // This would be used by Analysis Service or other services
            // For now, we log it - in production, dispatch a job or event
            Log::info("Task assigned", [
                'task_id' => $task->id,
                'child_id' => $childId,
                'content_id' => $task->content_id,
                'assigned_by' => $task->assigned_by,
                'timestamp' => $task->assigned_at,
            ]);
        } catch (\Throwable $e) {
            Log::error("Failed to notify services of task assignment: " . $e->getMessage());
            // Don't throw - task was created successfully, notification failure shouldn't block
        }
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
