<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WeeklySummaryResource;
use App\Models\WeeklySummary;

class WeeklySummaryController extends Controller
{
    public function show(string $childId)
    {
        $summary = WeeklySummary::where(
            'child_id',
            $childId
        )
            ->latest('week_start_date')
            ->first();

        if (!$summary) {
            return response()->json([
                'message' => 'No weekly summary found.'
            ], 404);
        }

        return new WeeklySummaryResource(
            $summary
        );
    }
}
