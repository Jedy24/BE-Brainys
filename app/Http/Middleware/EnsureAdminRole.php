<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAdminRole
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->roles !== 'admin') {
            abort(403, 'Unauthorized');
        }

        return $next($request);
    }
}
