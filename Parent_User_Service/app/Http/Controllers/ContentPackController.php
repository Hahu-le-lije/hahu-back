<?php

namespace App\Http\Controllers;

use App\Models\ContentPack;
use Illuminate\Http\JsonResponse;

class ContentPackController extends Controller
{
    public function index(): JsonResponse
    {
        $packs = ContentPack::query()
            ->where('is_active', true)
            ->whereNotNull('latest_published_version')
            ->with('latestPublishedVersion')
            ->orderBy('slug')
            ->get();

        $response = $packs->map(function (ContentPack $pack) {
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
        })->values();

        return response()->json([
            'contentPacks' => $response,
        ]);
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
            abort(404, 'No published version found for this content pack.');
        }

        return response()->json($version->payload);
    }

    // Add manifest() and download() methods as in the old version if needed
}
