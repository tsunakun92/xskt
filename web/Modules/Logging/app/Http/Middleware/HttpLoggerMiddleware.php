<?php

namespace Modules\Logging\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\HttpLogger\Middlewares\HttpLogger as SpatieHttpLogger;

/**
 * Wrapper middleware for Spatie HTTP Logger
 */
class HttpLoggerMiddleware extends SpatieHttpLogger {
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next) {
        return parent::handle($request, $next);
    }
}
