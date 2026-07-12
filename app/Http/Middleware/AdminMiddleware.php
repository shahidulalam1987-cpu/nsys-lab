<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && (
            auth()->user()->role === 'admin'
            || auth()->user()->hasAnyRole([
                'agency_owner',
                'agency_operations_manager',
                'moderator',
                'ad_manager',
                'auditor',
                'monitor',
                'trainer',
                'hr_manager',
                'finance_manager',
                'business_manager',
                'page_manager',
            ])
        )) {
            return $next($request);
        }

        abort(403);
    }
}
