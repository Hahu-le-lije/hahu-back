<?php

namespace App\Http\Controllers;

use App\Jobs\AssignSubscriptionToChild;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionAssignmentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'child_id' => ['required', 'integer', 'min:1'],
            'subscription_id' => ['required', 'string', 'max:100'],
        ]);

        AssignSubscriptionToChild::dispatch(
            childId: (int) $data['child_id'],
            subscriptionId: $data['subscription_id'],
        );

        return response()->json([
            'message' => 'Subscription assignment queued.',
        ], 202);
    }
}
