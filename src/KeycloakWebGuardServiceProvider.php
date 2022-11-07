<?php

namespace SlowCheetah\KeycloakWebGuard;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use SlowCheetah\KeycloakWebGuard\Auth\Guard\KeycloakWebGuard;
use SlowCheetah\KeycloakWebGuard\Auth\KeycloakWebUserProvider;
use SlowCheetah\KeycloakWebGuard\Middleware\KeycloakAuthenticated;
use SlowCheetah\KeycloakWebGuard\Middleware\KeycloakCan;
use SlowCheetah\KeycloakWebGuard\Services\KeycloakService;

class KeycloakWebGuardServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Configuration
        $config = __DIR__ . '/../config/keycloak-web.php';

        $this->publishes([$config => config_path('keycloak-web.php')], 'config');
        $this->mergeConfigFrom($config, 'keycloak-web');

        // User Provider
        Auth::provider('keycloak-users', function($app, array $config) {
            return new KeycloakWebUserProvider(
                $config['model'],
                $config['modelSearchField'],
                $config['keyCloakSearchField'],
                $config['userCreator'],
                $config['syncUser'],
            );
        });

        // Gate
        Gate::define('keycloak-web', function ($user, $roles, $resource = '') {
            return $user->hasRole($roles, $resource) ?: null;
        });
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // Keycloak Web Guard
        Auth::extend('keycloak-web', function ($app, $name, array $config) {
            $provider = Auth::createUserProvider($config['provider']);
            return new KeycloakWebGuard($provider, $app->request);
        });

        // Facades
        $this->app->bind('keycloak-web', function($app) {
            return $app->make(KeycloakService::class);
        });

        // Routes
        $this->registerRoutes();

        // Middleware Group
        $this->app['router']->middlewareGroup('keycloak-web', [
            StartSession::class,
            KeycloakAuthenticated::class,
        ]);

        // Add Middleware "keycloak-web-can"
        $this->app['router']->aliasMiddleware('keycloak-web-can', KeycloakCan::class);

        // Bind for client data
        $this->app->when(KeycloakService::class)->needs(ClientInterface::class)->give(function() {
            return new Client(Config::get('keycloak-web.guzzle_options', []));
        });
    }

    /**
     * Register the authentication routes for keycloak.
     *
     * @return void
     */
    private function registerRoutes()
    {
        $defaults = [
            'login' => 'login',
            'logout' => 'logout',
            'register' => 'register',
            'callback' => 'callback',
        ];

        $routes = Config::get('keycloak-web.routes', []);
        $routes = array_merge($defaults, $routes);

        // Register Routes
        $router = $this->app->make('router');

        if (! empty($routes['login'])) {
            $router->middleware('web')->get($routes['login'], 'SlowCheetah\KeycloakWebGuard\Controllers\AuthController@login')->name('keycloak.login');
        }

        if (! empty($routes['logout'])) {
            $router->middleware('web')->get($routes['logout'], 'SlowCheetah\KeycloakWebGuard\Controllers\AuthController@logout')->name('keycloak.logout');
        }

        if (! empty($routes['register'])) {
            $router->middleware('web')->get($routes['register'], 'SlowCheetah\KeycloakWebGuard\Controllers\AuthController@register')->name('keycloak.register');
        }

        if (! empty($routes['callback'])) {
            $router->middleware('web')->get($routes['callback'], 'SlowCheetah\KeycloakWebGuard\Controllers\AuthController@callback')->name('keycloak.callback');
        }
    }
}
