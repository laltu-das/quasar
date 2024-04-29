<?php

namespace Laltu\Quasar\Http\Middleware;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Laltu\Quasar\Services\LicenseChecker;

class LicenseGuardMiddleware
{

    /**
     * @throws ConnectionException
     */
    public function handle(Request $request, Closure $next)
    {
        if (! LicenseChecker::validateLicense($request)) {
            return abort(403, 'Invalid license');
        }

        return $next($request);
    }

}
