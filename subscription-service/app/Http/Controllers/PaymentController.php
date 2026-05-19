<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Chapa;
use App\Services\SubscriptionManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function showPaymentForm()
    {
        error_log("PaymentController::showPaymentForm - Rendering initpayment view.");
        return view('initpayment');
    }

    public static function initializePayment(Request $request)
    {
        error_log("PaymentController::initializePayment - Method started.");

        error_log("PaymentController::initializePayment - Validating request data.");
        $validatedData = $request->validate([
            'plan_type' => 'required|string',
            'max_slots' => 'required|integer|min:1',
        ]);
        error_log("PaymentController::initializePayment - Validation successful: " . json_encode($validatedData));

        $planType = $validatedData['plan_type'];
        $maxSlots = $validatedData['max_slots'];
        
        $ref = Chapa::generateReference('HahuSub_');
        error_log("generated ref " . $ref);
        
        try {
            error_log("PaymentController::initializePayment - Attempting to calculate plan amount for plan: {$planType}, slots: {$maxSlots}.");
            $calculatedAmount = SubscriptionManager::calculatePlanAmount($planType, $maxSlots);
            error_log("PaymentController::initializePayment - Successfully calculated amount: {$calculatedAmount}.");
        } catch (\Throwable $th) {
            error_log("PaymentController::initializePayment - Error calculating plan amount: " . $th->getMessage());
            return response()->json([
                'status' => 'failed',
                'error' => $th->getMessage()
            ], 201);
            //throw $th;
        }

        error_log("PaymentController::initializePayment - Preparing to call Chapa::initializePayment.");
        $response = Chapa::initializePayment([
            'tx_ref' => $ref,
            'amount' => $calculatedAmount,
            'currency' => 'ETB',
            'callback_url' => route('subscription.create'), //?  use this url for testing "https://from-chapa-payment.free.beeceptor.com"
            // 'return_url' => config('subscriptiontype.return_url'), //! I need to get the return url from the front end team
            // Customization object
            'customization' => [
                'title' => 'Hahu Lelije',
                'description' => $planType . ' subscription plan for ' . $maxSlots . ' users',
            ],
            // Meta object for additional tracking
            'meta' => [
                'user_id' => Auth::id(),
                'end_at' => now()->add(SubscriptionManager::getDuration()[$planType])->toDateTimeString(),
                'invoices' => [
                    [
                        'key' => 'plan_type',
                        'value' => $planType
                    ],
                    [
                        'key' => 'max_slots',
                        'value' => (string) $maxSlots
                    ],
                ]
            ]
        ]);
        
        error_log(json_encode($response));
        
        // 2. Validate that we actually got a URL back
        error_log("PaymentController::initializePayment - Validating Chapa response for checkout URL.");
        if ($response['status'] !== 'success' || !isset($response['data']['checkout_url'])) {
            error_log("PaymentController::initializePayment - Validation failed. Status: " . ($response['status'] ?? 'null') . ", URL set: " . (isset($response['data']['checkout_url']) ? 'true' : 'false'));
            return response()->json([
                'status' => 'failed',
                'error' => "invalid checkout url"
            ], 201);
        }

        $checkoutUrl = $response['data']['checkout_url'];
        error_log("PaymentController::initializePayment - Checkout URL retrieved successfully: " . $checkoutUrl);
        
        // 3. Redirect the user to the external Chapa checkout page
        error_log("PaymentController::initializePayment - Returning success response to client.");
        return response()->json([
            'status' => 'success',
            'data' => [
                'checkout_url' => $checkoutUrl
            ]
        ], 200);

    }

}