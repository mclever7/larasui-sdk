<?php

namespace Mclever\LarasuiSdk;

use Mclever\LarasuiSdk\SuiClient;
//use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class SuiServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Bind the SuiClient class into the service container
        $this->app->singleton('sui', function ($app) {
            // Use the configuration directly from the package
            $rpcUrl = $this->getConfig('rpc_url');
            //$rpcUrl = Config::get('sui.rpc_url');
            return new SuiClient($rpcUrl);
        });
    }

    public function boot()
    {
        // Merge the package configuration
        $this->mergeConfigFrom(
            $this->getConfigPath(), 'sui'
        );

        // Publish the config file (only works when installed in a Laravel app)
        $this->publishes([
            $this->getConfigPath() => $this->getPublishedConfigPath(),
        ], 'sui-config');
    }

    /**
     * Get the path to the package's configuration file.
     *
     * @return string
     */
    protected function getConfigPath()
    {
        return __DIR__ . '/../config/sui.php';
    }

    /**
     * Get the path to the published configuration file.
     *
     * @return string
     */
    protected function getPublishedConfigPath()
    {
        // Check if the app() function is available (Laravel environment)
        if (function_exists('app')) {
            return app()->configPath('sui.php');
        }

        // Fallback for non-Laravel environments
        return getcwd() . '/config/sui.php';
    }

    /**
     * Get a configuration value from the package's configuration file.
     *
     * @param string $key
     * @return mixed
     */
    protected function getConfig($key)
    {
        static $config = null;

        // Load the configuration file if not already loaded
        if ($config === null) {
            $config = require $this->getConfigPath();
        }

        return $config[$key] ?? null;
    }
}