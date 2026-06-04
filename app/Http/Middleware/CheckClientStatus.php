<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckClientStatus
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            auth()->check()
            && auth()->user()->role === 'client'
            && auth()->user()->status === 'inactive'
        ) {
            auth()->logout();

            return redirect('/login')
                ->with('error', 'Your account has been disabled. Please contact administrator.');
        }

        return $next($request);
    }
}