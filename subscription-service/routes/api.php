<?php   

use Illuminate\Http\Request;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

use function Pest\Laravel\json;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');



Route::post('/subscriptions/create', [SubscriptionController::class, 'createSubscription'])->name('subscription.create');

