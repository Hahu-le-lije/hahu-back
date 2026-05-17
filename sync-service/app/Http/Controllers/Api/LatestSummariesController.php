<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GameTypeSummaryService;

class LatestSummariesController extends Controller
{
    public function show(string $childId, GameTypeSummaryService $summaries): array
    {
        return $summaries->latestForChild($childId);
    }
}
