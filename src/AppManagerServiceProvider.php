<?php

namespace HulkApps\AppManager;

use Illuminate\Support\ServiceProvider;

class AppManagerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        /*
         * Optional methods to load your package assets
         */
        // $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'app-manager');
        // $this->loadViewsFrom(__DIR__.'/../resources/views', 'app-manager');
        // $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
         $this->loadRoutesFrom(__DIR__ . '/../routes/app-manager.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('app-manager.php'),
            ], 'config');

            $this->publishes([
                __DIR__.'/../config/plan_features.php' => config_path('plan_features.php'),
            ], 'config');
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // Automatically apply the package configuration
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'app-manager');

        // Register the main class to use with the facade
        $this->app->singleton('app-manager', function () {
            return new AppManager(config('app-manager.api'), config('app-manager.secret'));
        });
    }
}
