<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LearningEvent;
use App\Models\DailySummary;
use Illuminate\Http\Request;

class AiExportController extends Controller
{
    public function featureSnapshot(string $childId)
    {
        // Get last 10 days of summaries
        $daily = DailySummary::where('child_id', $childId)
            ->orderByDesc('summary_date')
            ->limit(10)
            ->get();

        if ($daily->isEmpty()) {
            return response()->json([
                'message' => 'No data available for AI export'
            ], 404);
        }

        $features = [
            'child_id' => $childId,

            'avg_accuracy' => $daily->avg('accuracy'),
            'avg_mastery_score' => $daily->avg('mastery_score'),
            'avg_time_spent' => $daily->avg('time_spent'),

            'trend_accuracy' =>
                $this->calculateTrend($daily->pluck('accuracy')->toArray()),

            'trend_mastery' =>
                $this->calculateTrend($daily->pluck('mastery_score')->toArray()),

            'consistency_score' =>
                $daily->avg('consistency'),

            'skill_diversity_avg' =>
                $daily->avg('skill_diversity'),

            'learning_stability' =>
                $this->calculateStability($daily),

            'data_window_days' => $daily->count(),
        ];

        return response()->json($features);
    }

    public function exportEvents(Request $request, string $childId)
    {
        $since = $request->query('since');

        $query = LearningEvent::where('child_id', $childId)
            ->orderBy('event_created_at');

        if ($since) {
            $query->where('event_created_at', '>=', $since);
        }

        $events = $query->limit(1000)->get();

        return response()->json([
            'child_id' => $childId,
            'count' => $events->count(),
            'events' => $events->map(function ($event) {
                return [
                    'event_id' => $event->event_id,
                    'game_type' => $event->game_type,
                    'score' => $event->score,
                    'time_spent' => $event->time_spent,
                    'metrics' => $event->metrics,
                    'skill_breakdown' => $event->skill_breakdown,
                    'event_created_at' => $event->event_created_at,
                ];
            }),
        ]);
    }

    private function calculateTrend(array $values): float
    {
        if (count($values) < 2) {
            return 0;
        }

        $first = $values[count($values) - 1];
        $last = $values[0];

        return round($last - $first, 4);
    }

    private function calculateStability($daily)
    {
        $values = $daily->pluck('mastery_score')->toArray();

        $mean = array_sum($values) / count($values);

        $variance = array_reduce($values, function ($carry, $item) use ($mean) {
                return $carry + pow($item - $mean, 2);
            }, 0) / count($values);

        return round(1 / (1 + $variance), 4);
    }
}
