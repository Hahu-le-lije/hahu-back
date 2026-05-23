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

use function Laravel\Prompts\error;

class SubscriptionController extends Controller
{

    public function __construct(protected InternalUserService $rpc)
    {
    }

    public function createSubscription(Request $request)
    {
        error_log('SubscriptionController::createSubscription - Method started.');
        error_log('Creating subscription with request: ' . json_encode($request->all()));

        $queryParams = $request->query();
        $gg = json_encode($queryParams);
        error_log('Extracted query parameters: ' . $gg);
        error_log('trx_ref: ' . $queryParams['trx_ref'] ?? 'N/A');
        error_log('status: ' . $queryParams['status'] ?? 'N/A');


        // 2. Run the validator against the query data array
        $validatedData = Validator::make($queryParams, [
            'trx_ref' => 'required|string',
            'status' => 'required|string|in:success,pending,failed',
        ])->validate();
        error_log('finishing validation');

        // Once validated, you can access the data safely
        $transactionReference = $validatedData['trx_ref'];
        $status = $validatedData['status'];

        error_log("SubscriptionController::createSubscription - Checking if transaction reference '{$transactionReference}' already exists in the database.");
        if (
            Subscription::query()
                ->Where('tx_ref', $transactionReference)->first()
        ) {
            error_log("SubscriptionController::createSubscription - Transaction '{$transactionReference}' already completed. Returning 400.");
            return response()->json([
                'status' => 'failed',
                'error' => 'Transaction already completed'
            ], 400);
        }

        // check if the transaction didn't exist in the database
        error_log("SubscriptionController::createSubscription - Verifying transaction with Chapa.");
        $verify = Chapa::verifyTransaction($transactionReference);
        error_log('verifying transaction with chapa ' . print_r($verify, true));

        // 3. Comprehensive Validation of the Chapa API Response
        error_log("SubscriptionController::createSubscription - Validating Chapa response structure.");
        $validator = Validator::make($verify, [
            'status' => 'required|string|in:success',

            'data.meta.user_id' => 'required|string',
            'data.meta.end_at' => 'required|date',
            'data.meta.invoices' => 'required|array|min:1',
            'data.meta.invoices.*.key' => 'required|string',
            'data.meta.invoices.*.value' => 'required|string',

            'data.amount' => 'required|numeric',
            'data.currency' => 'required|string',
        ]);
        if ($validator->fails()) {
            error_log("SubscriptionController::createSubscription - Chapa response validation failed.");
            return response()->json([
                'status' => 'failed',
                'error' => 'Transaction data structure is invalid',
            ], 422);
        }

        // 4. Assigning values to single PHP variables
        error_log("SubscriptionController::createSubscription - Chapa response validation passed. Extracting data.");
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

        error_log("SubscriptionController::createSubscription - Extracted data: Plan='{$plan_type}', Slots='{$max_slots}', Amount='{$amount}'");

        if ($status !== 'success' || !$plan_type || !$max_slots || !$end_at || $end_at->isPast()) {
            error_log("SubscriptionController::createSubscription - Payment status check failed or data missing/expired. Returning 400.");
            return response()->json([
                'status' => 'failed',
                'error' => 'Payment not successfully completed'
            ], 400);
        }

        try {
            error_log("SubscriptionController::createSubscription - Calculating plan amount for Plan='{$plan_type}' and Slots='{$max_slots}'.");
            $calculatedAmount = SubscriptionManager::calculatePlanAmount($plan_type, $max_slots);
        } catch (\Throwable $th) {
            error_log("SubscriptionController::createSubscription - Exception caught while calculating plan amount: " . $th->getMessage());
            return response()->json([
                'status' => 'failed',
                'error' => $th->getMessage()
            ], 400);
        }

        if ($amount != ($calculatedAmount)) {
            error_log("SubscriptionController::createSubscription - Amount mismatch. Chapa Amount='{$amount}', Calculated Amount='{$calculatedAmount}'. Returning 400.");
            return response()->json([
                'status' => 'failed',
                'error' => 'Amount mismatch'
            ], 400);
        }

        try {
            error_log("SubscriptionController::createSubscription - Attempting to cache/fetch user info for User ID: {$userId}");
            Cache::remember('user_' . $userId, now()->addMinutes(30), function () use ($userId) {
                error_log("Cache didn't store user info with id: {$userId}. Calling RPC getParent.");
                return $this->rpc->getParent($userId);
            });
        } catch (Exception $th) {
            error_log("SubscriptionController::createSubscription - Exception caught while fetching user data: " . $th->getMessage());
            return response()->json([
                'status' => 'failed',
                'error' => 'An error occurred while fetching user data.'
            ], 500);
        }

        // FIX 2: Explicitly create using the Subscription model to ensure 
        // owner_id is correctly mapped, and auto-set available_slots = max_slots
        error_log("SubscriptionController::createSubscription - Creating new Subscription record in database.");
        $subscription = Subscription::create([
            'owner_id' => $userId, // Use the authenticated user's ID
            'plan_type' => $plan_type,
            'available_slots' => $max_slots, // Initializes full
            'status' => 'active',
            'ends_at' => $end_at,
            'tx_ref' => $transactionReference,
        ]);

        error_log("SubscriptionController::createSubscription - Subscription created successfully with ID: {$subscription->id}. Returning 201.");
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
        error_log("SubscriptionController::addChildToSubscription - Method started. SubID: {$subscription->id}, ChildID: {$child_id}");
        //? if there were an exception thrown in the following lines, it would be caught by the global exception handler and a 500 response will be returned, so we don't need to handle it here.

        try {
            error_log("SubscriptionController::addChildToSubscription - Attempting to cache/fetch child info for Child ID: {$child_id}");
            $res = Cache::remember('child_' . $child_id, now()->addMinutes(30), function () use ($child_id) {
                error_log("SubscriptionController::addChildToSubscription - Cache miss for child ID: {$child_id}. Calling RPC getChild.");
                return $this->rpc->getChild($child_id);
            });

        } catch (Exception $th) {
            error_log("SubscriptionController::addChildToSubscription - Exception caught while fetching child data: " . $th->getMessage());
            return response()->json([
                'status' => 'failed',
                'error' => 'An error occurred while fetching child data.',
            ], 500);
        }

        $child = $res;
        error_log("SubscriptionController::addChildToSubscription - Validating child data structure.");
        error_log('child data: ' . print_r($child, true));
        // Validate the response structure
        if (!isset($child['parent_id'])) {
            error_log("SubscriptionController::addChildToSubscription - Invalid child response data structure. Missing subscription_id or parent_id.");
            return response()->json([
                'status' => 'failed',
                'error' => 'Invalid response data structure'
            ], 500);
        }


        error_log("SubscriptionController::addChildToSubscription - Validating ownership. Sub Owner ID: {$subscription->owner_id}, Child Parent ID: {$child['parent_id']}, Auth ID: " . Auth::id());
        // Validate ownership
        if ($subscription->owner_id != Auth::id() || $child['parent_id'] != Auth::id()) {
            error_log("SubscriptionController::addChildToSubscription - Unauthorized attempt to add child. Returning 403.");
            return response()->json([
                'status' => 'failed',
                'error' => 'Unauthorized'
            ], 403);
        }

        error_log("SubscriptionController::addChildToSubscription - Checking available slots: {$subscription->available_slots}");
        // Check if the subscription has available slots
        if ($subscription->available_slots <= 0) {
            error_log("SubscriptionController::addChildToSubscription - No available slots. Returning 400.");
            return response()->json([
                'status' => 'failed',
                'error' => 'No available slots in the subscription'
            ], 400);
        }

        error_log("SubscriptionController::addChildToSubscription - Checking existing subscription for Child Sub ID: {$child['subscription_id']}");
        
        if (isset($child['subscription_id'])) {
            $subscriptionOfChild = Subscription::findOrFail($child['subscription_id'])->first();
            $existingPlanType = $subscriptionOfChild->plan_type;
            $newPlanType = $subscription->plan_type;

            error_log("SubscriptionController::addChildToSubscription - Comparing fees. Existing Plan: {$existingPlanType}, New Plan: {$newPlanType}");
            $existingFee = SubscriptionManager::getTypes()[$existingPlanType];
            $newFee = SubscriptionManager::getTypes()[$newPlanType];

            if ($subscriptionOfChild->ends_at->isFuture() && $existingFee >= $newFee) {
                error_log("SubscriptionController::addChildToSubscription - Child already has an active subscription with equal or greater fee. Rejecting.");
                //? the user is neither have an expired subscription nor they are trying to upgrade to a more expensive plan, so we block the action and return an error message
                return response()->json([
                    'status' => 'failed',
                    'error' => 'Child is already associated with an active subscription'
                ], 400);
            } else if ($subscriptionOfChild->ends_at->isFuture()) {
                error_log("SubscriptionController::addChildToSubscription - Child upgrading to a better plan. Restoring 1 slot to previous subscription.");
                $subscriptionOfChild->update([
                    'available_slots' => $subscriptionOfChild->available_slots + 1, //? we increment the available slots of the old subscription to reflect that the child is no longer associated with it
                ]);
            }
        }
        // Check if the child is already associated with a subscription


        error_log("SubscriptionController::addChildToSubscription - Dispatching LinkChildSubscription job to queue.");
        // Wrap the updates in a database transaction to prevent data corruption 
        // if one of the queries fails.
        LinkChildSubscription::dispatch($child_id, $subscription->id)->onQueue('subscription_to_user');

        error_log("SubscriptionController::addChildToSubscription - Decrementing available slots on new subscription.");
        //? Decrement the available slots in the subscription
        $subscription->decrement('available_slots', 1);

        error_log("SubscriptionController::addChildToSubscription - Successfully processed. Returning 200.");
        return response()->json([
            'status' => 'success',
            'message' => 'Child added to subscription successfully'
        ], 200);
    }

    public function getSubscriptionDetails(string $subscription_id)
    {
        error_log("SubscriptionController::getSubscriptionDetails - Method started. SubID: {$subscription_id}");

        $subscription = Subscription::query()->find($subscription_id);
        if (!$subscription) {
            error_log("SubscriptionController::getSubscriptionDetails - Subscription not found. Returning 404.");
            return response()->json([
                'status' => 'failed',
                'error' => 'Subscription not found'
            ], 404);
        }

        error_log("SubscriptionController::getSubscriptionDetails - Validating ownership. Owner ID: {$subscription->owner_id}, Auth ID: " . Auth::id());
        if ($subscription->owner_id != Auth::id()) {// TODO: good to change the error msg for security purpose.
            error_log("SubscriptionController::getSubscriptionDetails - Unauthorized access attempt. Returning 403.");
            return response()->json([
                'status' => 'failed',
                'error' => 'Unauthorized'
            ], 403);
        }

        error_log("SubscriptionController::getSubscriptionDetails - Returning subscription details successfully.");
        return response()->json([
            'status' => 'success',
            'data' => [
                'subscription' => $subscription
            ]
        ], 200);
    }

    public function listUserSubscriptions()
    {
        error_log("SubscriptionController::listUserSubscriptions - Method started.");
        error_log('usr id: ' . Auth::id());

        $subscriptions = Subscription::query()
            ->where('owner_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        error_log("SubscriptionController::listUserSubscriptions - Fetched " . $subscriptions->count() . " subscriptions. Returning 200.");

        return response()->json([
            'status' => 'success',
            'data' => [
                'subscriptions' => $subscriptions
            ]
        ], 200);
    }
}