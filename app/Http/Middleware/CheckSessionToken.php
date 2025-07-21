<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckSessionToken
{
    public function handle($request, Closure $next)
    {
        if (Auth::check() && Auth::user()->session_token !== session('session_token')) {
            Auth::logout();
            return redirect()->route('get.login')->with(['error' => true, 'message' => 'Your session has expired. Please log in again.']);
        }

        return $next($request);
    }
}
