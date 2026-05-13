<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\LinkChildSubscription;
use App\Models\Subscription;
use App\Services\SubscriptionManager;
use App\Services\InternalUserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{

    public function __construct(protected InternalUserService $rpc)
    {
    }

    public function createSubscription(Request $request)
    {
        error_log('Creating subscription with request: ' . $request);
        $validatedData = $request->validate([
            'trx_ref' => 'required|string',
            'ref_id' => 'required|string',
            'status' => 'required|string|in:success,pending,failed',
        ]);
        error_log('finishing validation');

        // Once validated, you can access the data safely
        $transactionReference = $validatedData['trx_ref'];
        $status = $validatedData['status'];

        if (
            Subscription::query()
                ->Where('tx_ref', $transactionReference)->first()
        ) {
            return response()->json([
                'status' => 'failed',
                'error' => 'Transaction already completed'
            ], 400);
        }

        // check if the transaction didn't exist in the database
        $verify = Chapa::verifyTransaction($transactionReference);
        error_log('verifying transaction with chapa ' . print_r($verify, true));
        // 3. Comprehensive Validation of the Chapa API Response
        $validator = Validator::make($verify, [
            'status' => 'required|string|in:success',
            'data.meta.user_id' => 'required|integer',
            'data.meta.end_at' => 'required|date',

            // Validating the nested invoices array
            'data.meta.invoices' => 'required|array|min:1',
            'data.meta.invoices.*.key' => 'required|string',
            'data.meta.invoices.*.value' => 'required|string',

            'data.amount' => 'required|numeric',
            'data.currency' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'failed',
                'error' => 'Transaction data structure is invalid',
            ], 422);
        }

        // 4. Assigning values to single PHP variables
        $validated = $validator->validated();

        $userId = $validated['data']['meta']['user_id'];
        $end_at = Carbon::parse($validated['data']['meta']['end_at']);
        $invoices = $validated['data']['meta']['invoices']; // This remains an array
        $amount = $validated['data']['amount'];
        // $currency = $validated['data']['currency'];

        // Optional: Extract specific values from the invoices if needed
        $plan_type = null;
        $max_slots = null;

        foreach ($invoices as $item) {
            if ($item['key'] === 'plan_type')
                $plan_type = $item['value'];
            if ($item['key'] === 'max_slots')
                $max_slots = (int) $item['value'];
        }

        if ($status !== 'success' || !$plan_type || !$max_slots || !$end_at || $end_at->isPast()) {
            return response()->json([
                'status' => 'failed',
                'error' => 'Payment not successfully completed'
            ], 400);
        }

        if ($amount != (SubscriptionManager::calculatePlanAmount($plan_type, $max_slots))) {
            return response()->json([
                'status' => 'failed',
                'error' => 'Amount mismatch'
            ], 400);
        }

        try {
            $res = Cache::remember('user_' . $userId, now()->addMinutes(30), function () use ($userId) {
                error_log("Cache didn't store user info with id: {$userId}");
                return $this->rpc->call(
                    'subscription.to.user',
                    ['action' => 'get_parent', 'parent_id' => $userId]
                );
            }, );

            if (!$res || $res['status'] !== 'success') {
                return response()->json([
                    'status' => 'failed',
                    'error' => 'User not found'
                ], 404);
            }
        } catch (Exception $th) {
            return response()->json([
                'status' => 'failed',
                'error' => 'An error occurred while fetching user data.'
            ], 500);
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
            'status' => 'success',
            'message' => 'Subscription created successfully',
            'data' => [
                'subscription' => $subscription
            ]
        ], 201);
    }

    public function addChildToSubscription(Subscription $subscription, string $child_id)
    {
        //? if there were an exception thrown in the following lines, it would be caught by the global exception handler and a 500 response will be returned, so we don't need to handle it here.

        try {
            $res = Cache::remember('child_' . $child_id, now()->addMinutes(30), function () use ($child_id) {
                return $this->rpc->call(
                    'subscription.to.user',
                    ['action' => 'get_child', 'child_id' => $child_id]
                );
            });

        } catch (Exception $th) {
            return response()->json([
                'status' => 'failed',
                'error' => 'An error occurred while fetching child data.',
            ], 500);
        }

        if (!$res || $res['status'] !== 'success') {
            return response()->json([
                'status' => 'failed',
                'error' => 'Child not found'
            ], 404);
        }

        // Validate the response structure
        if (!isset($res['data']['subscription_id']) || !isset($res['data']['parent_id'])) {
            return response()->json([
                'status' => 'failed',
                'error' => 'Invalid response data structure'
            ], 500);
        }


        $child = $res['data'];

        // Validate ownership
        if ($subscription->owner_id != Auth::id() || $child['parent_id'] != Auth::id()) {
            return response()->json([
                'status' => 'failed',
                'error' => 'Unauthorized'
            ], 403);
        }

        // Check if the subscription has available slots
        if ($subscription->available_slots <= 0) {
            return response()->json([
                'status' => 'failed',
                'error' => 'No available slots in the subscription'
            ], 400);
        }

        // Check if the child is already associated with a subscription
        $subscriptionOfChild = Subscription::findOrFail($child['subscription_id'])->first();
        $existingPlanType = $subscriptionOfChild->plan_type;
        $newPlanType = $subscription->plan_type;
        $existingFee = SubscriptionManager::getTypes()[$existingPlanType];
        $newFee = SubscriptionManager::getTypes()[$newPlanType];

        if ($subscriptionOfChild->ends_at->isFuture() && $existingFee >= $newFee) {
            //? the user is neither have an expired subscription nor they are trying to upgrade to a more expensive plan, so we block the action and return an error message
            return response()->json([
                'status' => 'failed',
                'error' => 'Child is already associated with an active subscription'
            ], 400);
        } else if ($subscriptionOfChild->ends_at->isFuture()) {
            $subscriptionOfChild->update([
                'available_slots' => $subscriptionOfChild->available_slots + 1, //? we increment the available slots of the old subscription to reflect that the child is no longer associated with it
            ]);
        }


        // Wrap the updates in a database transaction to prevent data corruption 
        // if one of the queries fails.
        LinkChildSubscription::dispatch($child_id, $subscription->id)->onQueue('subscription.to.user');

        //? Decrement the available slots in the subscription
        $subscription->decrement('available_slots', 1);
        return response()->json([
            'status' => 'success',
            'message' => 'Child added to subscription successfully'
        ], 200);
    }

    public function getSubscriptionDetails(string $subscription_id)
    {

        $subscription = Subscription::query()->find($subscription_id);
        if (!$subscription) {
            return response()->json([
                'status' => 'failed',
                'error' => 'Subscription not found'
            ], 404);
        }
        if ($subscription->owner_id != Auth::id()) {// TODO: good to change the error msg for security purpose.
            return response()->json([
                'status' => 'failed',
                'error' => 'Unauthorized'
            ], 403);
        }
        return response()->json([
            'status' => 'success',
            'data' => [
                'subscription' => $subscription
            ]
        ], 200);
    }

    public function listUserSubscriptions()
    {
        // Retrieve all subscriptions for the authenticated user
        error_log('usr id: ' . Auth::id());
        $subscriptions = Subscription::query()
                ->where('owner_id', Auth::id())
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();


        return response()->json([
            'status' => 'success',
            'data' => [
                'subscriptions' => $subscriptions
            ]
        ], 200);
    }
}