<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DailySummaryResource;
use App\Models\DailySummary;

class DailySummaryController extends Controller
{
    public function show(string $childId)
    {
        $summary = DailySummary::where(
            'child_id',
            $childId
        )
            ->latest('summary_date')
            ->first();

        if (!$summary) {
            return response()->json([
                'message' => 'No daily summary found.'
            ], 404);
        }

        return new DailySummaryResource(
            $summary
        );
    }
}
