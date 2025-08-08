<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // If user hasn't completed setup, redirect to wizard
        if (!$user->setup_completed) {
            return redirect()->route('setup.wizard');
        }

        // Check if user has any of the required roles
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        // If no roles match, check for special access permissions
        if (in_array('any', $roles)) {
            return $next($request);
        }

        // Business owners have access to everything
        if ($user->isBusinessOwner()) {
            return $next($request);
        }

        // Unauthorized access
        abort(403, 'Unauthorized access. You do not have permission to access this resource.');
    }
}