<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class JwtAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'No token provided'], 401);
        }

        try {
            // Both services use the same 'JWT_SECRET' from .env
            $decoded = JWT::decode($token, new Key(config('auth.jwt_secret'), 'HS256'));

            // "Hydrate" a virtual user so Laravel's Auth::user() works
            $user = new User();
            $user->id = $decoded->user_id; // 'sub' is usually the user_id
            $user->name = ($decoded->first_name ?? 'ServiceUser') . ' ' . ($decoded->last_name ?? ''); // Optional, for convenience

            Auth::setUser($user); // Now Service B has an "Authenticated" user

        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        return $next($request);
    }
}
