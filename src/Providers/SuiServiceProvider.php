<?php

namespace Mclever\LarasuiSdk\Providers;

use Mclever\LarasuiSdk\SuiClient;
use Illuminate\Support\ServiceProvider;

class SuiServiceProvider extends ServiceProvider
{
    /**
     * Register services into the Laravel container.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->bind('sui', function ($app) {
            return new SuiClient();
        });
    }

    /**
     * Bootstrap package services.
     *
     * @return void
     */
    public function boot(): void
    {
        // Publish config file with standard 'config' tag
        $this->publishes([
            __DIR__ . '/../../config/sui.php' => config_path('sui.php'),
        ], 'config');
    }
}
