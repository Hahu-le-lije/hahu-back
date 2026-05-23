<?php

namespace App\Http\Controllers;

use App\Models\ContentPackVersion;
use App\Support\ContentPayloadFormatter;
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

    // Create a new version
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
        $validated['payload'] = ContentPayloadFormatter::normalize($validated['payload']);
        $version = ContentPackVersion::create($validated);
        // If this version is published, make it the pack's latest published version
        if (! empty($validated['published_at'])) {
            try {
                $pack = \App\Models\ContentPack::find($validated['content_pack_id']);
                if ($pack) {
                    $pack->latest_published_version = $version->id;
                    $pack->save();
                }
            } catch (\Throwable $e) {
                // Don't fail the request just for a best-effort update; log the error
                \Log::warning('Failed to update latest_published_version', ['error' => $e->getMessage(), 'content_pack_id' => $validated['content_pack_id'], 'version_id' => $version->id]);
            }
        }
        return response()->json($version, 201);
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
            $validated['payload'] = ContentPayloadFormatter::normalize($validated['payload']);
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
