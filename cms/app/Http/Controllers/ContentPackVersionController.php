<?php

namespace App\Http\Controllers;

use App\Models\ContentPack;
use App\Models\ContentPackVersion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class ContentPackVersionController extends Controller
{
    // List all versions for a content pack
    public function index(Request $request): JsonResponse
    {
        $query = ContentPackVersion::query();
        if ($request->has('content_pack_id')) {
            $query->where('content_pack_id', $request->input('content_pack_id'));
        }
        $versions = $query->orderByDesc('published_at')->orderByDesc('id')->get();
        return response()->json($versions);
    }

    /**
     * Normalizes incoming payload to store only the core data in the DB.
     */
    private function normalizePayload(array $payload, string $gameType): array
    {
        // Use the null coalescing operator to avoid "Undefined index" errors
        return match ($gameType) {
            'story_quiz' => $payload['stories'] ?? [],
            
            'fidel_tracing' => $payload['fidel_tracing']['levels'] ?? [],
            
            'word_builder', 
            'voice_to_word', 
            'fill_in_the_blank', 
            'picture_to_word' => $payload['content']['levels'] ?? [],
            
            default => $payload['content']['levels'] ?? ($payload['content'] ?? [])
        };
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content_pack_id' => 'required|exists:content_packs,id',
            'version'         => 'required|string',
            'payload'         => 'required|array',
        ]);

        $payload = $request->input('payload');
        $gameType = $payload['game_type'] ?? 'default';

        $version = ContentPackVersion::create([
            'content_pack_id' => $validated['content_pack_id'],
            'version'         => $validated['version'],
            'meta'            => $payload['meta'] ?? [],
            'game_type'       => $gameType,
            'content'         => $this->normalizePayload($payload, $gameType),
        ]);

        return response()->json(['message' => 'Saved successfully', 'id' => $version->id], 201);
    }

    public function show($id): JsonResponse
    {
        $version = ContentPackVersion::findOrFail($id);

        $response = [
            'meta'           => $version->meta,
            'schema_version' => 2,
            'game_type'      => $version->game_type,
        ];

        // Reconstruct the structure for the mobile app
        switch ($version->game_type) {
            case 'story_quiz':
                $response['stories'] = $version->content;
                break;
            case 'fidel_tracing':
                $response['fidel_tracing'] = ['levels' => $version->content];
                break;
            default:
                // This covers all formats that use {"content": {"levels": ...}}
                $response['content'] = ['levels' => $version->content];
                break;
        }

        return response()->json($response);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $version = ContentPackVersion::findOrFail($id);

        $validated = $request->validate([
            'version'      => 'sometimes|required|string|max:255',
            'payload'      => 'sometimes|required|array',
            'published_at' => 'nullable|date',
        ]);

        if ($request->has('payload')) {
            $payload = $request->input('payload');
            $gameType = $payload['game_type'] ?? $version->game_type;

            // Apply strict normalization before saving to the DB
            $version->content   = $this->normalizePayload($payload, $gameType);
            $version->game_type = $gameType;
            $version->meta      = $payload['meta'] ?? $version->meta;
        }

        $version->update($request->except(['payload', 'game_type']));
        
        return response()->json(['message' => 'Updated successfully', 'version' => $version]);
    }

    public function destroy($id): JsonResponse
    {
        $version = ContentPackVersion::findOrFail($id);
        $version->delete();
        return response()->json(['message' => 'Content pack version deleted']);
    }
}