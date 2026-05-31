<?php

use Illuminate\Http\Request;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Middleware\ClerkAuthMiddleware;
use App\Http\Middleware\AuthenticateInternalService;
use App\Models\Subscription;

use function Pest\Laravel\json;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(ClerkAuthMiddleware::class)->group(function () {
    Route::post('/initialize-payment', [PaymentController::class, 'initializePayment'])->name('pay.initialize');
    Route::put('/subscriptions/add-child/{subscription}/{child}', [SubscriptionController::class, 'addChildToSubscription'])->whereNumber('subscription');
    Route::get('/subscriptions/list', [SubscriptionController::class, 'listUserSubscriptions']); //? order matters
    Route::get('/subscriptions/{subscription}', [SubscriptionController::class, 'getSubscriptionDetails'])->whereNumber('subscription');
});

Route::prefix('internal')
    ->middleware(AuthenticateInternalService::class)
    ->group(function () {
        Route::get('/subscriptions/{subscriptionId}', [SubscriptionController::class, 'getSubscriptionDetails'])->whereNumber('subscriptionId');
    });


Route::get('/subscriptions/create', [SubscriptionController::class, 'createSubscription'])->name('subscription.create');

