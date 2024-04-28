<?php

namespace Laltu\Quasar\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laltu\Quasar\Supports\DomainSupport;
use Pdp\CannotProcessHost;

class LicenseGuardMiddleware
{

    public function handle(Request $request, Closure $next)
    {
        if (! $this->validateDomain($request)) {
            return abort(403, 'Invalid license');
        }

        return $next($request);
    }

    /**
     * Validates the domain and sets it on the request.
     *
     * @param Request $request
     * @return bool
     */
    private function validateDomain(Request $request): bool
    {
        $host = $request->header('host');

        if (!$host) {
            return false;
        }

        $domain = DomainSupport::validateDomain($host);
        dd($domain);
        try {
            $domain = DomainSupport::validateDomain($host);
            dd($domain);

        } catch (CannotProcessHost $e) {
            return false;
        }

        $registrableDomain = $domain->registrableDomain()->toString();
dd($registrableDomain);
        if (!empty($registrableDomain)) {
            $request->merge(['domain' => $registrableDomain]);
            return true;
        }

        return false;
    }
}
