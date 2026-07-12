<?php

namespace App\Http\Middleware;

use App\Services\ActivityLogger;
use App\Services\PermissionRouteRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DepartmentPermissionMiddleware
{
    public function __construct(private PermissionRouteRegistry $registry)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $permissions = $this->registry->permissionsFor($request);

        if ($permissions
            && ! in_array(PermissionRouteRegistry::SUPER_ADMIN_ONLY, $permissions, true)
            && $user->hasAnyPermission($permissions)) {
            return $next($request);
        }

        if (! $request->isMethod('GET')) {
            app(ActivityLogger::class)->log(
                'Security',
                'Admin Route Denied',
                'Denied '.$request->method().' '.$request->path().' for '.$user->primaryRoleName().'.',
                $request
            );
        }

        abort(403, 'Your role cannot access this department.');
    }
}
