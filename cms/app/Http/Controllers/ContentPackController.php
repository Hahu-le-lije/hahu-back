<?php

namespace App\Http\Controllers;

use App\Models\ContentPack;
use App\Support\ContentPayloadFormatter;
use App\Support\ContentSchemaValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContentPackController extends Controller
{
    public function index(): JsonResponse
    {
        $packs = ContentPack::query()
            ->where('is_active', true)
            ->whereNotNull('latest_published_version')
            ->with('latestPublishedVersion')
            ->orderBy('slug')
            ->get()
            ->map(function (ContentPack $pack) {
                $version = $pack->latestPublishedVersion;

                return [
                    'id' => $pack->slug,
                    'title' => $pack->title,
                    'description' => $pack->description,
                    'gameType' => $pack->game_type,
                    'thumbnail' => $pack->thumbnail_url,
                    'size' => $pack->size_mb,
                    'version' => $version?->version,
                    'checksum' => $version?->checksum,
                    'sizeBytes' => $version?->size_bytes,
                    'minAppVersion' => $version?->min_app_version,
                    'manifestUrl' => route('content.packs.manifest', ['slug' => $pack->slug]),
                    'downloadUrl' => route('content.packs.download', ['slug' => $pack->slug]),
                ];
            })
            ->values();

        return response()->json([
            'contentPacks' => $packs,
        ]);
    }

    public function adminIndex(): JsonResponse
    {
        $packs = ContentPack::query()
            ->with('latestPublishedVersion')
            ->withCount('versions')
            ->orderBy('slug')
            ->get()
            ->map(function (ContentPack $pack) {
                $version = $pack->latestPublishedVersion;

                return [
                    'id' => $pack->id,
                    'slug' => $pack->slug,
                    'title' => $pack->title,
                    'description' => $pack->description,
                    'game_type' => $pack->game_type,
                    'thumbnail_url' => $pack->thumbnail_url,
                    'size_mb' => $pack->size_mb,
                    'is_active' => $pack->is_active,
                    'versions_count' => $pack->versions_count,
                    'latest_published_version' => $version ? [
                        'id' => $version->id,
                        'version' => $version->version,
                        'published_at' => optional($version->published_at)->toISOString(),
                    ] : null,
                ];
            })
            ->values();

        return response()->json($packs);
    }

    public function show(string $id): JsonResponse
    {
        $pack = ContentPack::query()
            ->with('latestPublishedVersion')
            ->findOrFail($id);

        return response()->json([
            'id' => $pack->id,
            'slug' => $pack->slug,
            'title' => $pack->title,
            'description' => $pack->description,
            'game_type' => $pack->game_type,
            'thumbnail_url' => $pack->thumbnail_url,
            'size_mb' => $pack->size_mb,
            'is_active' => $pack->is_active,
            'latest_published_version' => $pack->latestPublishedVersion ? [
                'id' => $pack->latestPublishedVersion->id,
                'version' => $pack->latestPublishedVersion->version,
                'published_at' => optional($pack->latestPublishedVersion->published_at)->toISOString(),
            ] : null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'slug' => 'required|string|max:255|unique:content_packs,slug',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'game_type' => 'nullable|string|max:255',
            'thumbnail_url' => 'nullable|string|max:2048',
            'size_mb' => 'nullable|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $pack = ContentPack::create($validated);

        return response()->json($pack, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $pack = ContentPack::query()->findOrFail($id);

        $validated = $request->validate([
            'slug' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('content_packs', 'slug')->ignore($pack->id)],
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'game_type' => 'nullable|string|max:255',
            'thumbnail_url' => 'nullable|string|max:2048',
            'size_mb' => 'nullable|numeric|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $pack->update($validated);

        return response()->json($pack);
    }

    public function destroy(string $id): JsonResponse
    {
        $pack = ContentPack::query()->findOrFail($id);
        $pack->delete();

        return response()->json(['message' => 'Content pack deleted']);
    }

    public function manifest(string $slug): JsonResponse
    {
        $pack = ContentPack::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with('latestPublishedVersion')
            ->firstOrFail();

        $version = $pack->latestPublishedVersion;

        if (! $version) {
            abort(404, 'No published version found for this content pack.');
        }

        return response()->json([
            'id' => $pack->slug,
            'title' => $pack->title,
            'description' => $pack->description,
            'gameType' => $pack->game_type,
            'thumbnail' => $pack->thumbnail_url,
            'version' => $version->version,
            'checksum' => $version->checksum,
            'sizeBytes' => $version->size_bytes,
            'minAppVersion' => $version->min_app_version,
            'publishedAt' => optional($version->published_at)->toISOString(),
            'downloadUrl' => route('content.packs.download', ['slug' => $pack->slug]),
        ]);
    }

    public function download(string $slug): JsonResponse
    {
        $pack = ContentPack::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->with('latestPublishedVersion')
            ->firstOrFail();

        $version = $pack->latestPublishedVersion;

        if (! $version) {
            abort(404, 'No published version found.');
        }

        // QUERY THE CONTENT TABLE INSTEAD OF VERSION PAYLOAD
        $contentItems = \App\Models\Content::where('content_pack_version_id', $version->id)
            ->active()
            ->ordered()
            ->get();

        // Transform into the structure your app expects
        $data = $contentItems->map(fn($item) => [
            'type' => $item->type,
            'title' => $item->title,
            'description' => $item->description,
            'content' => $item->content,
            'difficulty' => $item->difficulty,
        ]);

        return response()->json($data);
    }
}
