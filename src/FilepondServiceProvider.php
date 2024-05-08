<?php

namespace Laltu\Quasar;

use Exception;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Inertia\Response;
use Inertia\ResponseFactory;
use Laltu\Quasar\Services\LicenseChecker;

class FilepondServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        Rule::macro('filepond', function ($args) {
            return new FilepondRule($args);
        });

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/filepond.php' => base_path('config/filepond.php'),
            ], 'filepond-config');

            if (! class_exists('CreateFilepondsTable')) {
                $this->publishes([
                    __DIR__.'/../database/migrations/create_fileponds_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_fileponds_table.php'),
                ], 'filepond-migrations');
            }

            $this->commands([
                FilepondClear::class,
            ]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filepond.php', 'filepond');

        $this->app->singleton('filepond', function () {
            return new Filepond;
        });
    }
}
