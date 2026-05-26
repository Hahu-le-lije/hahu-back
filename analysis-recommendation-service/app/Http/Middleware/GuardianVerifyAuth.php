<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Services\ChildServiceClient;
use Symfony\Component\HttpFoundation\Response;

class GuardianVerifyAuth
{
    protected ChildServiceClient $childService;

    public function __construct(ChildServiceClient $childService)
    {
        $this->childService = $childService;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $childId = $request->route('childId');
        $currentGuardianId = Auth::id(); // The authenticated parent making the request
        error_log("GuardianVerifyAuth@handle - Start - Child: {$childId}, Guardian: {$currentGuardianId}");

        if (!$childId) {
            error_log("GuardianVerifyAuth@handle - Missing childId in route");
            return response()->json(['message' => 'Child ID parameter is missing in the route.'], 400);
        }

        if (!$currentGuardianId) {
            error_log("GuardianVerifyAuth@handle - Unauthenticated guardian");
            return response()->json(['message' => 'Unauthenticated guardian.'], 401);
        }

        $cacheKey = "child_parent_pair_{$childId}";

        // 1. Check if the child-parent pair is already cached
        if (Cache::has($cacheKey)) {
            error_log("GuardianVerifyAuth@handle - Cache hit for: {$cacheKey}");
            $actualParentId = Cache::get($cacheKey);
        } else {
            error_log("GuardianVerifyAuth@handle - Cache miss for: {$cacheKey}, fetching profile");
            // 2. If not cached, fetch the child profile using the provided JWT

            $childProfile = $this->childService->getAuthenticatedChildProfile($childId);

            if (!$childProfile || !isset($childProfile['id']) || !isset($childProfile['parent_id'])) {
                error_log("GuardianVerifyAuth@handle - Failed to fetch child profile or invalid structure for child: {$childId}");
                return response()->json(['message' => 'Invalid child token or profile is unavailable.'], 401);
            }

            $fetchedChildId = $childProfile['id'];
            $actualParentId = $childProfile['parent_id'];

            // 3. Cache the resolved child-parent pair for future requests (e.g., for 7 days)
            error_log("GuardianVerifyAuth@handle - Caching pair: Child {$fetchedChildId} -> Parent {$actualParentId}");
            Cache::put("child_parent_pair_{$fetchedChildId}", $actualParentId, now()->addDays(7));

            // Ensure the token returned actually matches the childId they are trying to query in the URL
            if ((string) $fetchedChildId !== (string) $childId) {
                error_log("GuardianVerifyAuth@handle - Token mismatch! Requested: {$childId}, Token: {$fetchedChildId}");
                return response()->json(['message' => 'Token does not match the requested child ID.'], 403);
            }
        }

        // 4. Verify Ownership: Does the resolved parent ID match the current authenticated user?
        if ((string) $actualParentId !== (string) $currentGuardianId) {
            error_log("GuardianVerifyAuth@handle - Forbidden access! Child: {$childId}, Actual Parent: {$actualParentId}, Request Guardian: {$currentGuardianId}");
            return response()->json(['message' => 'Forbidden. You are not the verified guardian of this child.'], 403);
        }

        error_log("GuardianVerifyAuth@handle - Verification successful for child: {$childId}, Guardian: {$currentGuardianId}");
        return $next($request);
    }
}
