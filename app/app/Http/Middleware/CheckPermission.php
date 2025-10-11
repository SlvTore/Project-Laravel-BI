<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckPermission
{
    /**
     * Handle an incoming request.
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $user = Auth::user();

        if ($user->hasPermission($permission)) {
            return $next($request);
        }

        Log::warning('Permission denied', [
            'user_id' => $user->id,
            'permission' => $permission,
            'route' => $request->route()?->getName(),
            'path' => $request->path(),
        ]);
        abort(403, 'You do not have permission: '.$permission);
    }
}
