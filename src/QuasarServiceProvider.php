<?php

namespace Laltu\Quasar;

use Exception;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Laltu\Quasar\Commands\InstallQuaserProject;
use Laltu\Quasar\Http\Middleware\ApplicationInstallMiddleware;
use Laltu\Quasar\Http\Middleware\ApplicationUpdateMiddleware;
use Laltu\Quasar\Http\Middleware\LicenseGuardMiddleware;
use Laltu\Quasar\Services\ConnectorService;

class QuasarServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot(Kernel $kernel, Router $router): void
    {

        /*
         * Optional methods to load your package assets
         */
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'quasar');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'quasar');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__ . '/routes.php');

        // Register middleware globally

//        $kernel->setGlobalMiddleware([LicenseGuardMiddleware::class]);

        // Register middleware globally
//        $kernel->appendMiddlewareToGroup('web', LicenseGuardMiddleware::class);

//        $kernel->appendMiddlewareToGroup('web', ApplicationInstallMiddleware::class);
//        $kernel->appendMiddlewareToGroup('web', ApplicationUpdateMiddleware::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('routes.php'),
            ], 'config');

            // Publishing the views.
            /*$this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/quasar'),
            ], 'views');*/

            // Publishing assets.
            $this->publishes([
                __DIR__ . '/../public' => public_path('vendor/quasar'),
            ], ['assets', 'laravel-assets']);

            // Publishing the translation files.
            /*$this->publishes([
                __DIR__.'/../resources/lang' => resource_path('lang/vendor/quasar'),
            ], 'lang');*/
        }

        // Registering package commands.
        $this->commands([
            InstallQuaserProject::class
        ]);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'quasar');

        // Register the main class to use with the facade
        $this->app->singleton('quasar', function () {
            return new QuasarManager;
        });


        $this->app->singleton('license-connector', function ($app) {
            // Retrieve the license key from your application's configuration
            $licenseKey = config('license-connector.license_key');

            // Check if the license key is properly set
            if (empty($licenseKey)) {
                throw new Exception("License key is not set in the configuration.");
            }

            // Return a new instance of ConnectorService with the license key
            return new ConnectorService($licenseKey);
        });

    }
}
