<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

use Modules\Logging\Utils\LogHandler;

class CheckRoutePermission {
    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed {
        // Get the current route name
        $routeName = $request->route()->getName();

        // If no route name
        if (!$routeName) {
            return $next($request);
        }

        // If route name contains ajax, skip permission check
        if (str_contains($routeName, 'ajax')) {
            return $next($request);
        }

        // Route action to permission mapping
        $routePermissionMap = [
            'store'   => 'create',
            'update'  => 'edit',
            'destroy' => 'delete',
        ];

        // Check mapped permissions
        foreach ($routePermissionMap as $routeAction => $permissionAction) {
            if (str_contains($routeName, $routeAction) && can_access(str_replace($routeAction, $permissionAction, $routeName))) {
                return $next($request);
            }
        }

        // Check if user has permission to access this route
        if (!can_access($routeName)) {
            LogHandler::warning('Access denied - no permission to access route', [
                'route_name' => $routeName,
                'url'        => $request->fullUrl(),
                'method'     => $request->method(),
            ]);
            abort(403);
        }

        return $next($request);
    }
}
