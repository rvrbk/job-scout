<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class RequireSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->hasAccess()) {
            return $next($request);
        }

        $message = __('This needs an active subscription.');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 402);
        }

        Inertia::flash('toast', ['type' => 'error', 'message' => $message]);

        return to_route('billing.index');
    }
}
