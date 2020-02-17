<?php

namespace ViaWork\LeverPhp;

use Illuminate\Support\ServiceProvider;
use MyApp\LaravelRateLimiterStore;
use ViaWork\LeverPhp\Facade\Lever;

class LeverPhpServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {

        // $this->loadRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/config.php' => config_path('lever-php.php'),
            ], 'config');

            // Registering package commands.
            // $this->commands([]);
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/config.php', 'lever');

        // Register the main class to use with the facade
        $this->app->singleton('lever-php', static function () {
            return new LeverPhp(config('lever.key'), null, new LaravelRateLimiterStore());
        });

        $this->app->alias(Lever::class, 'Lever');
    }
}
