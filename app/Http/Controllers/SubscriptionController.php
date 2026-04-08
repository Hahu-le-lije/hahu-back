<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Services\SubscriptionManager;
use App\Services\RabbitRpcClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Exception;
class SubscriptionController extends Controller
{

    public function __construct(protected RabbitRpcClient $rpc)
    {
    }

    public function createSubscription(Request $request)
    {
        $validatedData = $request->validate([
            'trx_ref' => 'required|string',
            'ref_id' => 'required|string',
            'status' => 'required|string|in:success,pending,failed',

            // Validate the meta object and its internal array
            'meta' => 'required|array',
            'meta.user_id' => 'required|string',
            'meta.end_at' => 'required|string', // Each item in the invoices array must be an array
            'meta.invoices' => 'required|array',
            'meta.invoices.*.key' => 'required_with:meta.invoices|string',
            'meta.invoices.*.value' => 'required_with:meta.invoices|string',
        ]);

        // Once validated, you can access the data safely
        $transactionReference = $validatedData['trx_ref'];
        $status = $validatedData['status'];
        $plan_type = $validatedData['meta']['invoices'][0]['value'] ?? null;
        $max_slots = $validatedData['meta']['invoices'][1]['value'] ?? null;
        $end_at_str = $validatedData['meta']['end_at'] ?? null;
        $end_at = Carbon::parse($end_at_str);
        $userId = $validatedData['meta']['user_id'] ?? null;

        if ($status !== 'success' || !$plan_type || !$max_slots || !$end_at || $end_at->isPast()) {
            return response()->json(['message' => 'Payment not successfuly Completed'], 400);
        }


        // check if the transaction didn't exist in the database
        if (Subscription::Where('tx_ref', $transactionReference)->first()) {
            return response()->json(['message' => 'Transaction already completed'], 400);
        }

        $resFromChapa = Chapa::verifyTransaction($transactionReference);
        if (!$resFromChapa || !$resFromChapa['status'] || $resFromChapa['status'] !== 'success') {
            return response()->json(['message' => 'Transaction verification failed'], 400);
        }

        if ($resFromChapa['data']['amount'] != (SubscriptionManager::calculatePlanAmount($plan_type, $max_slots))) {
            return response()->json(['message' => 'Amount mismatch'], 400);
        }

        try {
            $res = $this->rpc->call('parent_service_queue', ['action' => 'get_parent', 'parent_id' => $userId]);
            if (!$res || $res['status'] !== 'success') { //! $res['status'] might need to be change into  $res->status 
                return response()->json(['message' => 'User not found'], 404);
            }
        } catch (Exception $th) {
            return response()->json(['message' => 'An error occurred while fetching user data.'], 500);
        }

        // FIX 2: Explicitly create using the Subscription model to ensure 
        // owner_id is correctly mapped, and auto-set available_slots = max_slots
        $subscription = Subscription::create([
            'owner_id' => $userId, // Use the authenticated user's ID
            'plan_type' => $plan_type,
            'available_slots' => $max_slots, // Initializes full
            'status' => 'active',
            'ends_at' => $end_at,
            'tx_ref' => $transactionReference,
        ]);

        return response()->json([
            'message' => 'Subscription created successfully',
            'subscription' => $subscription
        ], 201);
    }

    public function addChildToSubscription(Subscription $subscription, string $child)
    {
        //? if there were an exception thrown in the following lines, it would be caught by the global exception handler and a 500 response will be returned, so we don't need to handle it here.
        $res = $this->rpc->call(
            'user_service_queue',
            ['action' => 'get_child', 'child_id' => $child]
        );

        if (!$res || $res['status'] !== 'success') {
            return response()->json(['message' => 'Child not found'], 404);
        }

        $child = $res['data']; //! we might need to change this into $res->data depending on how the RabbitMQ response is structured
        // Validate ownership
        if ($subscription->owner_id !== Auth::id() || $child->parent_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if the child is already associated with a subscription
        if ($child->subscription_id) {
            $subscriptionOfChild = Subscription::findOrFail($child->subscription_id)->first();
            $existingPlanType = $subscriptionOfChild->plan_type;
            $newPlanType = $subscription->plan_type;
            $existingFee = SubscriptionManager::getTypes()[$existingPlanType];
            $newFee = SubscriptionManager::getTypes()[$newPlanType];

            if ($subscriptionOfChild->ends_at->isFuture() && $existingFee >= $newFee) {
                //? the user is neither have an expired subscription nor they are trying to upgrade to a more expensive plan, so we block the action and return an error message
                return response()->json(['message' => 'Child is already associated with an active subscription'], 400);
            }
        }

        // Check if the subscription has available slots
        if ($subscription->available_slots <= 0) {
            return response()->json(['message' => 'No available slots in the subscription'], 400);
        }

        // Wrap the updates in a database transaction to prevent data corruption 
        // if one of the queries fails.
        try {
            $res = $this->rpc->call(
                'child_service_queue',
                ['action' => 'link_child_subscription', 'child_id' => $child->id, 'subscription_id' => $subscription->id]
            );
            if (!$res || $res->status !== 'success') {
                return response()->json(['message' => 'Failed to link child to subscription'], 500);
            }
            return response()->json(['message' => 'Child added to subscription successfully'], 200);
        } catch (Exception $e) {
            // Catch database errors and return a 500 response
            return response()->json(['message' => 'An error occurred while linking the child.'], 500);
        }
    }

    public function getSubscriptionDetails(Subscription $subscription)
    {
        if ($subscription->owner_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return response()->json(['subscription' => $subscription], 200);
    }

    public function listUserSubscriptions()
    {
        // Retrieve all subscriptions for the authenticated user
        $subscriptions = Subscription::where('owner_id', Auth::id())->get();
        return response()->json(['subscriptions' => $subscriptions], 200);
    }
}