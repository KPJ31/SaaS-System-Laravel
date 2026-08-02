<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmployeeActiveMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== 'employee') {
            return $next($request);
        }

        if (! $user->company_id) {
            abort(403, 'Your employee account is not connected to a company.');
        }

        if ($user->status !== 'active') {
            abort(403, 'Your employee account has been suspended. Please contact your Company Admin.');
        }

        return $next($request);
    }
}
