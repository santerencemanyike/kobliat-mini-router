<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RequireApiToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('X-API-TOKEN') ?: $request->bearerToken();
        $expected = env('API_TOKEN') ?: config('app.api_token');

        if (! $expected || ! $token || ! hash_equals($expected, $token)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
