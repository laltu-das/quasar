<?php

namespace Laltu\Quasar\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laltu\Quasar\Supports\DomainSupport;
use Pdp\CannotProcessHost;

class LicenseGuardMiddleware
{
    /**
     * @throws CannotProcessHost
     */
    public function handle(Request $request, Closure $next)
    {
        $host = $request->header('_Host');
        $hostName = $request->header('_Host_Name');

        if ($host && $hostName) {
            $domain = DomainSupport::validateDomain($host);
            $subDomain = $domain->subDomain()->toString();

            if (config('license-server.allow_subdomains') && !empty($subDomain)) {
                $request->merge([
                    'domain' => $subDomain,
                ]);

                return $next($request);
            }

            $registrableDomain = $domain->registrableDomain()->toString();

            if (!empty($registrableDomain)) {
                $request->merge([
                    'domain' => $registrableDomain,
                ]);

                return $next($request);
            }
        }

        return abort(403,'Invalid license');
    }
}
