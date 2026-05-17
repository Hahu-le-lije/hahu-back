<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LearningSessionIngestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearningSessionController extends Controller
{
    public function store(Request $request, LearningSessionIngestionService $ingestion): JsonResponse
    {
        $data = $request->validate([
            'sessions' => ['required', 'array', 'min:1'],
        ]);

        $result = $ingestion->ingest($data['sessions']);

        return response()->json([
            'message' => 'Learning sessions accepted.',
            ...$result,
        ], 202);
    }
}
