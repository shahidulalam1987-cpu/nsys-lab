<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user && ($user->isSuperAdmin() || $user->hasAnyRole($roles))) {
            return $next($request);
        }

        abort(403, 'You do not have the required role.');
    }
}
