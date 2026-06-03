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

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'content_pack_id' => 'required|exists:content_packs,id',
            'version'         => 'required|string',
            'payload'         => 'required|array', // Accepts the JSON object
            'checksum'        => 'required|string',
            'size_bytes'      => 'required|integer',
            'min_app_version' => 'nullable|string',
        ]);

        // PASS-THROUGH: Store the payload exactly as it arrived.
        // No indexing, no extraction, no transformation.
        $version = ContentPackVersion::create([
            'content_pack_id' => $validated['content_pack_id'],
            'version'         => $validated['version'],
            'checksum'        => $validated['checksum'],
            'content'         => $validated['payload'], // Raw JSON object stored
            'game_type'       => $validated['payload']['game_type'] ?? 'unknown',
            'meta'            => [
                'min_app_version' => $validated['min_app_version'],
                'size_bytes'      => $validated['size_bytes'],
            ],
        ]);

        return response()->json(['message' => 'Saved successfully', 'id' => $version->id], 200);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $version = ContentPackVersion::findOrFail($id);

        $request->validate([
            'version'         => 'sometimes|required|string|max:255',
            'payload'         => 'sometimes|required|array',
            'checksum'        => 'sometimes|required|string',
            'min_app_version' => 'nullable|string',
            'size_bytes'      => 'nullable|integer',
        ]);

        if ($request->has('payload')) {
            $fullPayload = $request->input('payload')[0]; 
            
            // Directly assign the raw payload to 'content'
            $version->content   = $fullPayload; 
            $version->game_type = $fullPayload['game_type'] ?? $version->game_type;
            
            // Update meta
            $version->meta = [
                'min_app_version' => $request->input('min_app_version', $version->meta['min_app_version'] ?? null),
                'size_bytes'      => $request->input('size_bytes', $version->meta['size_bytes'] ?? null),
         ];
        }

        if ($request->has('checksum')) {
            $version->checksum = $request->input('checksum');
        }

        $version->save();
        
        return response()->json(['message' => 'Updated successfully', 'version' => $version]);
    }

    public function destroy($id): JsonResponse
    {
        $version = ContentPackVersion::findOrFail($id);
        $version->delete();
        return response()->json(['message' => 'Content pack version deleted']);
    }
}