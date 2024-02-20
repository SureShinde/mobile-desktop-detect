<?php

namespace SureShinde\MobileDesktopDetect;

use Illuminate\Support\ServiceProvider;

class AgentServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected bool $defer = true;

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->singleton('agent', function ($app) {
            return new Agent($app['request']->server());
        });

        $this->app->alias('agent', Agent::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides(): array
    {
        return ['agent', Agent::class];
    }
}
