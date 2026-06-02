<?php

namespace App\Http\Controllers;

use App\Models\ContentPack;
use App\Models\ContentPackVersion;
use App\Support\ContentPayloadFormatter;
use App\Support\ContentSchemaValidator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content_pack_id' => 'required|exists:content_packs,id',
            'version' => 'required|string|max:255',
            'checksum' => 'required|string',
            'size_bytes' => 'required|integer',
            'payload' => 'required|array',
            'min_app_version' => 'required|string',
            'published_at' => 'nullable|date',
        ]);

        if (empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        // Wrap in a transaction so if content save fails, version isn't created
        return \DB::transaction(function () use ($validated) {
            $version = ContentPackVersion::create($validated);

            foreach ($validated['payload'] as $index => $itemData) {
                \App\Models\Content::create([
                    'content_pack_version_id' => $version->id,
                    'type' => $itemData['type'] ?? 'default',
                    'title' => $itemData['title'] ?? 'Untitled',
                    'content' => $itemData['content'] ?? [],
                    'sequence_order' => $index,
                    'is_active' => true,
                ]);
            }

            return response()->json($version, 201);
        });
    }

    // Update a version
    public function update(Request $request, $id): JsonResponse
    {
        $version = ContentPackVersion::findOrFail($id);
        $validated = $request->validate([
            'version' => 'sometimes|required|string|max:255',
            'checksum' => 'sometimes|required|string',
            'size_bytes' => 'sometimes|required|integer',
            'payload' => 'sometimes|required|array',
            'min_app_version' => 'sometimes|required|string',
            'published_at' => 'nullable|date',
        ]);

        if (isset($validated['payload']) && is_array($validated['payload'])) {
            $validated['payload'] = ContentSchemaValidator::validateAndNormalize(
                (string) $version->contentPack?->game_type,
                ContentPayloadFormatter::normalize($validated['payload'])
            );
        }

        $version->update($validated);
        return response()->json($version);
    }

    // Delete a version
    public function destroy($id): JsonResponse
    {
        $version = ContentPackVersion::findOrFail($id);
        $version->delete();
        return response()->json(['message' => 'Content pack version deleted']);
    }
}