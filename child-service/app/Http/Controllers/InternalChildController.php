<?php

namespace App\Http\Controllers;

use App\Models\Child;
use Illuminate\Http\JsonResponse;

class InternalChildController extends Controller
{
    public function show(string $childId): JsonResponse
    {
        $child = Child::query()->find($childId);

        if ($child === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Child not found.',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->childPayload($child),
        ]);
    }

    public function childrenForSubscription(string $subscriptionId): JsonResponse
    {
        $children = Child::query()
            ->where('subscription_id', $subscriptionId)
            ->orderBy('first_name')
            ->get()
            ->map(fn (Child $child) => [
                'child_id' => (string) $child->getKey(),
                'child_name' => trim($child->first_name.' '.($child->last_name ?? '')),
                'parent_id' => $child->parent_id,
                'status' => $child->status,
            ])
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => $children,
        ]);
    }

    private function childPayload(Child $child): array
    {
        return [
            'id' => (string) $child->getKey(),
            'child_id' => (string) $child->getKey(),
            'parent_id' => $child->parent_id,
            'first_name' => $child->first_name,
            'last_name' => $child->last_name,
            'child_name' => trim($child->first_name.' '.($child->last_name ?? '')),
            'username' => $child->username,
            'avatar' => $child->avatar,
            'subscription_id' => $child->subscription_id,
            'age' => $child->age,
            'birthdate' => $child->birthdate,
            'skill_level' => $child->skill_level,
            'status' => $child->status,
            'last_login_at' => $child->last_login_at,
            'credentials_rotated_at' => $child->credentials_rotated_at,
            'created_at' => $child->created_at,
            'updated_at' => $child->updated_at,
        ];
    }
}
