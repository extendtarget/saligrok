<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\User;

class jwtMiddleware
{
    public function handle($request, Closure $next)
    {
        try {
            $token = $request->input('token');
            if (!$token) {
                \Log::warning('Token not provided in request: ' . json_encode($request->all()));
                return response()->json(['error' => 'Token not provided'], 401);
            }

            JWTAuth::setToken($token);

            $user = JWTAuth::authenticate();
            if (!$user) {
                \Log::warning('User not found for token: ' . $token);
                return response()->json(['error' => 'User not found'], 401);
            }

            $user = User::find($user->id);
            if (!$user) {
                \Log::warning('User not found in database for ID: ' . $user->id);
                return response()->json(['error' => 'User not found'], 401);
            }

           
            $isStoreOwner = $user->restaurants()->exists();

            if (!$isStoreOwner) {
               
                if (!$user->auth_token) {
                    \Log::warning('auth_token is null for user ID: ' . $user->id . ', rejecting token: ' . $token);
                    return response()->json(['error' => 'Invalid token - user logged out'], 401);
                }

                if ($user->auth_token !== $token) {
                    \Log::warning('Token mismatch for user ID: ' . $user->id . ', provided token: ' . $token . ', stored auth_token: ' . $user->auth_token);
                    return response()->json(['error' => 'Invalid token'], 401);
                }
            }

            if (!JWTAuth::parseToken()->check()) {
                \Log::warning('Token is blacklisted: ' . $token);
                return response()->json([
                    'success' => false,
                    'message' => 'The token has been blacklisted'
                ], 401);
            }

            \Log::info('Token validated successfully for user ID: ' . $user->id . ', is_store_owner: ' . ($isStoreOwner ? 'true' : 'false'));
        } catch (\Exception $e) {
            \Log::error('Middleware error: ' . $e->getMessage());
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}