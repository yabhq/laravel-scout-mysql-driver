<?php

namespace DamianTW\MySQLScout\Providers;

use Illuminate\Support\ServiceProvider;

use Laravel\Scout\EngineManager;

use DamianTW\MySQLScout\Engines\MySQLEngine;

use DamianTW\MySQLScout\Services\ModelService;

use DamianTW\MySQLScout\Services\IndexService;

use DamianTW\MySQLScout\Commands\ManageIndexes;

class MySQLScoutServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ManageIndexes::class
            ]);
        }

        resolve(EngineManager::class)->extend('mysql', function () {
            return new MySQLEngine(resolve(IndexService::class));
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ModelService::class, function ($app) {
            return new ModelService;
        });

        $this->app->singleton(IndexService::class, function ($app) {
            return new IndexService($app->make(ModelService::class));
        });
    }
}
