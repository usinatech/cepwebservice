<?php

declare(strict_types=1);

namespace UsinaTech\CEPWebservice;

use Illuminate\Support\ServiceProvider;

class CEPWebserviceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/cepwebservice.php', 'cepwebservice');

        $connectionConfig = array_merge(
            (array) config('cepwebservice.connection', []),
            (array) config('database.connections.sqliteCEPWebservice', [])
        );

        config()->set('database.connections.sqliteCEPWebservice', $connectionConfig);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');

        $this->publishes([
            __DIR__ . '/../config/cepwebservice.php' => config_path('cepwebservice.php'),
        ], 'cepwebservice-config');

        $this->publishes([
            __DIR__ . '/../database/cepwebservice.sqlite.zip' => database_path('cepwebservice.sqlite.zip'),
        ], 'cepwebservice-database');
    }
}
