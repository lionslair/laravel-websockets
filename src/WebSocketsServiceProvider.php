<?php

namespace BeyondCode\LaravelWebSockets;

use BeyondCode\LaravelWebSockets\Dashboard\EventSubscriber;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use BeyondCode\LaravelWebSockets\ClientProviders\ClientProvider;
use Illuminate\Support\ServiceProvider;
use BeyondCode\LaravelWebSockets\LaravelEcho\Pusher\Channels\ChannelManager;

class WebSocketsServiceProvider extends ServiceProvider
{
    public function boot()
    {
        Route::middlewareGroup('websockets', config('websockets.dashboard.middleware', []));

        $this->publishes([
            __DIR__.'/../config/websockets.php' => base_path('config/websockets.php'),
        ], 'config');

        $this->registerRoutes();

        $this->registerDashboardGate();

        $this->loadViewsFrom(__DIR__.'/../resources/views/', 'websockets');

        $this->commands([
            Console\StartWebSocketServer::class,
        ]);

        Event::subscribe(EventSubscriber::class);
    }

    protected function registerRoutes()
    {
        Route::group($this->routeConfiguration(), function () {
            $this->loadRoutesFrom(__DIR__.'/Dashboard/Http/routes.php');
        });
    }

    protected function routeConfiguration()
    {
        return [
            'prefix' => config('websockets.dashboard.path'),
            'middleware' => 'websockets',
        ];
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/websockets.php', 'websockets');

        $this->app->singleton('websockets.router', function() {
            return new Router();
        });

        $this->app->singleton(ChannelManager::class, function() {
            return new ChannelManager();
        });

        $this->app->singleton(ClientProvider::class, function() {
            return app(config('websockets.client_provider'));
        });
    }

    protected function registerDashboardGate()
    {
        Gate::define('viewWebSocketsDashboard', function ($user = null) {
            return app()->environment('local');
        });
    }
}