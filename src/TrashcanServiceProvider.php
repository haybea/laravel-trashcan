<?php

namespace Haybea\Trashcan;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Haybea\Trashcan\Http\Middleware\Authorize;
use Haybea\Trashcan\Services\{ActivityLogger, ExportService, ModelDiscoveryService, StatisticsService};

class TrashcanServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/trashcan.php', 'trashcan');
        $this->app->singleton(Trashcan::class, fn ($app) => new Trashcan($app));
        $this->app->singleton(ModelDiscoveryService::class);
        $this->app->singleton(ActivityLogger::class);
        $this->app->singleton(ExportService::class);
        $this->app->singleton(StatisticsService::class);
    }

    public function boot(): void
    {
        Route::group([
            'prefix' => config('trashcan.path'),
            'namespace' => 'Haybea\Trashcan\Http\Controllers',
            'middleware' => array_merge(config('trashcan.middleware', ['web']), [Authorize::class]),
        ], fn () => $this->loadRoutesFrom(__DIR__.'/../routes/web.php'));

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'trashcan');

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/trashcan.php' => config_path('trashcan.php')], 'trashcan-config');
            $this->publishes([__DIR__.'/../resources/views' => resource_path('views/vendor/trashcan')], 'trashcan-views');
            $this->publishes([__DIR__.'/../database/migrations' => database_path('migrations')], 'trashcan-migrations');
        }

        Gate::define(config('trashcan.gate', 'viewTrashcan'), fn ($user) => true);
    }
}