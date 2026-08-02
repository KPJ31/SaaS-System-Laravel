<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionActiveMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role === 'super_admin') {
            return $next($request);
        }

        $subscription = $user->company?->activeSubscription;

        if (! $subscription || ! in_array($subscription->status, ['trialing', 'active'], true)) {
            abort(403, 'Your company subscription is not active.');
        }

        if ($subscription->ends_at && $subscription->ends_at->isPast()) {
            abort(403, 'Your company subscription has expired.');
        }

        return $next($request);
    }
}
