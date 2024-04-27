<?php

namespace Laltu\Quasar\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Laltu\Quasar\Services\InstallationService;
use Log;

class ApplicationInstallMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Path to the 'installed.lock' file in the storage directory
        $installationFlag = 'installed.lock';
        $installationService = new InstallationService();

        try {
            // Check if the application is installed by looking for the 'installed.lock' file
            if (!Storage::exists($installationFlag) && !$installationService->validateInstallation()) {
                // If the 'installed.lock' file does not exist, redirect to an installation route
                return redirect('/install');
            }
        } catch (Exception $e) {
            // Log the error or handle it as required
            // For now, we will log and redirect to a generic error page or maintenance page
//            Log::error('Failed to access the installation check file: ' . $e->getMessage());
            return response()->view('errors.maintenance', [], 503);
        }

        // If the application is installed, continue with the request
        return $next($request);
    }
}
