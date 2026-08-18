<?php

namespace Haybea\Trashcan\Http\Middleware;

use Closure;
use Haybea\Trashcan\Trashcan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class Authorize
{
    public function handle(Request $request, Closure $next): Response
    {
        // Allow access in configured environments (like local)
        if (app()->environment(config('trashcan.allowed_environments', ['local']))) {
            return $next($request);
        }

        // Check custom auth callback first
        if (Trashcan::check($request)) {
            return $next($request);
        }

        // Get user based on configured guard
        $guard = config('trashcan.guard');
        $user = $guard ? Auth::guard($guard)->user() : $request->user();

        // Fall back to gate check for authenticated users
        if ($user && Gate::forUser($user)->allows(config('trashcan.gate', 'viewTrashcan'))) {
            return $next($request);
        }

        abort(403, 'Unauthorized access to Trashcan.');
    }
}
