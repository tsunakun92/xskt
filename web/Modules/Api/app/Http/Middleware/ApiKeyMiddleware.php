<?php

namespace Modules\Api\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validate X-API-KEY header.
 */
class ApiKeyMiddleware {
    /**
     * Handle incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response {
        $apiKey      = $request->header('X-API-KEY');
        $expectedKey = config('api.api_key');

        if (empty($expectedKey)) {
            return $next($request);
        }

        if (empty($apiKey) || $apiKey !== $expectedKey) {
            return response()->json([
                'status'  => 0,
                'message' => 'Unauthorized: Invalid or missing X-API-KEY header',
            ], 401);
        }

        return $next($request);
    }
}
