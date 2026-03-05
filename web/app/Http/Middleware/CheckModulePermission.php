<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use Modules\Logging\Utils\LogHandler;

class CheckModulePermission {
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request  Current request instance
     * @param  Closure  $next  Next callback handler
     * @param  string  $module  Module name (e.g., admin, api)
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $module): mixed {
        // Module permission key format: {module}.module (e.g., admin.module, api.module)
        $modulePermissionKey = $module . '.module';

        // Check if user has permission to access the module
        if (!can_access($modulePermissionKey)) {
            LogHandler::warning('Access denied - no permission to access module', [
                'module' => $module,
                'key'    => $modulePermissionKey,
                'url'    => $request->fullUrl(),
                'method' => $request->method(),
            ]);

            abort(403);
        }

        return $next($request);
    }
}
