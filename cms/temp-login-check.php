<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$user = App\Models\User::where('email', 'admin@example.com')->first();
var_dump((bool) $user);
if ($user) {
    var_dump(Illuminate\Support\Facades\Hash::check('adminpassword', $user->password));
    var_dump(Illuminate\Support\Facades\Auth::attempt(['email' => 'admin@example.com', 'password' => 'adminpassword']));
    $token = $user->createToken('admin-token')->plainTextToken;
    echo $token, PHP_EOL;
}
