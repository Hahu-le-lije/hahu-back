<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Chapa;
use App\Services\SubscriptionManager;
use App\Services\RabbitRpcClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function showPaymentForm()
    {
        return view('initpayment');
    }
    public static function initializePayment(Request $request)
    {

        $validatedData = $request->validate([
            'plan_type' => 'required|string',
            'max_slots' => 'required|integer|min:1',
        ]);
        $planType = $validatedData['plan_type'];
        $maxSlots = $validatedData['max_slots'];
        $ref = Chapa::generateReference('HahuSub_'.Auth::id());
        
        $response = Chapa::initializePayment([
            'tx_ref' => $ref,
            'amount' => SubscriptionManager::calculatePlanAmount($planType, $maxSlots),
            'currency' => 'ETB',
            'callback_url' => route('subscription.create'),
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
                        'value' => $maxSlots
                    ],
                ]
            ]
        ]);

        // 2. Validate that we actually got a URL back
        if ($response['status'] !== 'success' || !isset($response['data']['checkout_url'])) {
            return response()->json(['status' => 'failed', 'message' => "invalid checkout url"], 201);
            }

        $checkoutUrl = $response['data']['checkout_url'];
        // 3. Redirect the user to the external Chapa checkout page
        return response()->json(['status' => 'success', 'checkout_url' => $checkoutUrl], 200);

    }

}
