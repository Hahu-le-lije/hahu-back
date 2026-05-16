<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DailySummaryResource;
use App\Http\Resources\WeeklySummaryResource;
use App\Models\DailySummary;
use App\Models\WeeklySummary;

class AnalyticsOverviewController extends Controller
{
    public function show(string $childId)
    {
        $daily = DailySummary::where(
            'child_id',
            $childId
        )
            ->latest('summary_date')
            ->first();

        $weekly = WeeklySummary::where(
            'child_id',
            $childId
        )
            ->latest('week_start_date')
            ->first();

        return response()->json([
            'daily_summary' =>
                $daily
                    ? new DailySummaryResource($daily)
                    : null,

            'weekly_summary' =>
                $weekly
                    ? new WeeklySummaryResource($weekly)
                    : null,
        ]);
    }
}
