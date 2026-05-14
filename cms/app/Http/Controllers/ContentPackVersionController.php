<?php

namespace App\Http\Controllers;

use App\Models\ContentPackVersion;
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
        $version = ContentPackVersion::create($validated);
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
