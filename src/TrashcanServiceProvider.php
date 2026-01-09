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
        $this->registerRoutes();
        $this->registerPublishing();
        $this->registerViews();
        $this->registerGate();
    }

    protected function registerRoutes(): void
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }

    protected function routeConfiguration(): array
    {
        return [
            'prefix' => config('trashcan.path'),
            'namespace' => 'Haybea\Trashcan\Http\Controllers',
            'middleware' => array_merge(
                config('trashcan.middleware', ['web']),
                [Authorize::class]
            ),
        ];
    }

    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            // Config
            $this->publishes([
                __DIR__.'/../config/trashcan.php' => config_path('trashcan.php'),
            ], 'trashcan-config');

            // Views
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/trashcan'),
            ], 'trashcan-views');

            // Migrations
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'trashcan-migrations');

            // All assets
            $this->publishes([
                __DIR__.'/../config/trashcan.php' => config_path('trashcan.php'),
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'trashcan');
        }
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'trashcan');
    }

    protected function registerGate(): void
    {
        Gate::define(config('trashcan.gate', 'viewTrashcan'), function ($user) {
            return true; // Override in AuthServiceProvider
        });
    }
}