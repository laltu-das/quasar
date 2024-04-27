<?php

namespace Laltu\Quasar\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApplicationUpdateMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }
}
