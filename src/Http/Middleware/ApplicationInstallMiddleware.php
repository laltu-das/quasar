<?php

namespace Laltu\Quasar\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Laltu\Quasar\Services\InstallationService;

class ApplicationInstallMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws ConnectionException
     */
    public function handle(Request $request, Closure $next)
    {
        // Path to the 'installed.lock' file in the storage directory
        $installationFlag = 'installed.lock';
        $installationService = new InstallationService();


        // Check if the application is installed by looking for the 'installed.lock' file
        if (!Storage::exists($installationFlag) && !$installationService->validateInstallation()) {
            // If the 'installed.lock' file does not exist, redirect to an installation route
            return redirect('/install');
        }

        // If the application is installed, continue with the request
        return $next($request);
    }
}
