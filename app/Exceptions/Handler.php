<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        //
    ];

    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    public function render($request, Exception $exception)
    {
        if ($request->is('api/*') || $request->expectsJson() || $request->wantsJson()) {
            // معالجة استثناء التوكن المُدرج في القائمة السوداء
            if ($exception instanceof TokenBlacklistedException) {
                \Log::warning('Token blacklisted: ' . $exception->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'The token has been blacklisted'
                ], 401);
            }

            if ($exception instanceof TokenInvalidException) {
                \Log::error('Invalid token: ' . $exception->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid Token'
                ], 401);
            }

            if ($exception instanceof TokenExpiredException) {
                \Log::error('Expired token: ' . $exception->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Token Expired'
                ], 401);
            }

            if ($exception instanceof JWTException) {
                \Log::error('JWT error: ' . $exception->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Token Error'
                ], 401);
            }

            if ($exception instanceof AuthenticationException) {
                \Log::warning('Unauthenticated: ' . $exception->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }

            if ($exception instanceof HttpException) {
                \Log::error('HTTP error: ' . $exception->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => $exception->getMessage() ?: 'Error'
                ], $exception->getStatusCode());
            }

            \Log::error('General error: ' . $exception->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred',
                'error' => $exception->getMessage()
            ], 500);
        }

        return parent::render($request, $exception);
    }
}