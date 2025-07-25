<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSetupCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && !Auth::user()->setup_completed) {
            // Allow access to setup routes, logout, and API routes
            if ($request->routeIs('setup.*') ||
                $request->routeIs('logout') ||
                $request->is('api/*')) {
                return $next($request);
            }

            return redirect()->route('setup.wizard');
        }

        return $next($request);
    }
}
