<?php

namespace Dskripchenko\LaravelLogstash\Providers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\CachesConfiguration;
use Illuminate\Support\ServiceProvider;

class LogstashServiceProvider extends ServiceProvider
{
    /**
     * @return void
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__, 2) . '/config/logging.php',
            'logging');
    }

    /**
     * @param $path
     * @param $key
     * @return void
     * @throws BindingResolutionException
     */
    protected function mergeConfigFrom($path, $key): void
    {
        if (! ($this->app instanceof CachesConfiguration && $this->app->configurationIsCached())) {
            $config = $this->app->make('config');

            $config->set($key, array_merge_deep(
                require $path, $config->get($key, [])
            ));
        }
    }

}