<?php

namespace Haybea\Trashcan\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;
use Haybea\Trashcan\Trashcan;

class Authorize
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment(config('trashcan.allowed_environments', ['local']))) return $next($request);
        if (Trashcan::check($request)) return $next($request);
        if ($request->user() && Gate::allows(config('trashcan.gate', 'viewTrashcan'))) return $next($request);
        abort(403, 'Unauthorized access to Trashcan.');
    }
}