<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TiktokApiAuth
{
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('X-API-KEY');

        if (!$key || $key !== config('services.tiktok.secret')) {
            return response()->json([
                'error' => 'Unauthorized'
            ], 403);
        }

        return $next($request);
    }
}
