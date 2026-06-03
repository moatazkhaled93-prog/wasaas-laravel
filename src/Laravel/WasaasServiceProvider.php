<?php

namespace Wasaas\Laravel;

use Illuminate\Support\ServiceProvider;
use Wasaas\WasaasClient;

class WasaasServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/wasaas.php', 'wasaas');

        $this->app->singleton(WasaasClient::class, function () {
            return new WasaasClient(
                apiKey:  config('wasaas.api_key'),
                baseUrl: config('wasaas.base_url'),
            );
        });

        $this->app->alias(WasaasClient::class, 'wasaas');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/wasaas.php' => config_path('wasaas.php'),
        ], 'wasaas-config');
    }
}
