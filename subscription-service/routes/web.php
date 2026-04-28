<?php

use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubscriptionController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('clerk.auth')->group(function () {
    Route::post('/initialize-payment', [PaymentController::class, 'initializePayment'])->name('pay.initialize');
    Route::put('/subscriptions/add-child/{subscription}/{child}', [SubscriptionController::class, 'addChildToSubscription'])->whereNumber('subscription');
    Route::get('/subscriptions/{subscription}', [SubscriptionController::class, 'getSubscriptionDetails'])->whereNumber('subscription');
    Route::get('/subscriptions/list', [SubscriptionController::class, 'listUser Subscriptions']);
});
Route::get('/get-subscription-types', [PaymentController::class, 'showPaymentForm'])->name('subscription.types'); //! for debugging, remove later፣ including the controller method 

// The callback url after a payment
// Route::get('/callback/{reference}', 'App\Http\Controllers\ChapaController@callback')->name('payment.callback');
