<?php

namespace App\Http\Controllers;

use App\Models\ChildSubject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubjectController extends Controller
{
    private const GAME_TYPES = [
        1 => 'Fidel Tracing',
        2 => 'Fidel Match',
        3 => 'Pic-to-Word',
        4 => 'Word Builder',
        5 => 'Listen & Fill',
        6 => 'Speak Up',
        7 => 'Story Quiz',
    ];

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'child_id' => 'required|string|max:255',
        ]);

        $childId = trim($validated['child_id']);
        $this->ensureDefaultsForChild($childId);

        $subjects = ChildSubject::query()
            ->where('child_id', $childId)
            ->orderBy('game_type_id')
            ->get(['game_type_id', 'game_type_name', 'status'])
            ->map(fn (ChildSubject $row) => [
                'game_type_id' => (int) $row->game_type_id,
                'game_type_name' => (string) $row->game_type_name,
                'status' => (bool) $row->status,
            ])
            ->values();

        return response()->json([
            'subjects' => $subjects,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'child_id' => 'required|string|max:255',
            'subjects' => 'required|array|min:1',
            'subjects.*.game_type_id' => 'required|integer|min:1|max:7',
            'subjects.*.status' => 'required|boolean',
        ]);

        $childId = trim($validated['child_id']);
        $updates = $validated['subjects'];

        $this->ensureDefaultsForChild($childId);

        DB::transaction(function () use ($childId, $updates): void {
            foreach ($updates as $entry) {
                $gameTypeId = (int) $entry['game_type_id'];
                if (!array_key_exists($gameTypeId, self::GAME_TYPES)) {
                    continue;
                }

                ChildSubject::query()
                    ->where('child_id', $childId)
                    ->where('game_type_id', $gameTypeId)
                    ->update([
                        'status' => (bool) $entry['status'],
                    ]);
            }
        });

        $subjects = ChildSubject::query()
            ->where('child_id', $childId)
            ->orderBy('game_type_id')
            ->get(['game_type_id', 'game_type_name', 'status'])
            ->map(fn (ChildSubject $row) => [
                'game_type_id' => (int) $row->game_type_id,
                'game_type_name' => (string) $row->game_type_name,
                'status' => (bool) $row->status,
            ])
            ->values();

        return response()->json([
            'subjects' => $subjects,
        ]);
    }

    private function ensureDefaultsForChild(string $childId): void
    {
        foreach (self::GAME_TYPES as $gameTypeId => $gameTypeName) {
            ChildSubject::query()->firstOrCreate(
                [
                    'child_id' => $childId,
                    'game_type_id' => $gameTypeId,
                ],
                [
                    'game_type_name' => $gameTypeName,
                    'status' => true,
                ]
            );
        }
    }
}