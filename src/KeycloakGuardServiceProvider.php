<?php

namespace KeycloakGuard;

use Illuminate\Auth\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;

class KeycloakGuardServiceProvider extends ServiceProvider
{
    public function boot()
    {
         $this->registerMigrations();
         
        $this->publishes([__DIR__.'/../config/keycloak.php' => config_path('keycloak.php')], 'config');
        $this->mergeConfigFrom(__DIR__.'/../config/keycloak.php', 'keycloak');

        $this->publishes(
                [
                    __DIR__.'/../database/migrations' => database_path('migrations'),
                ],
                'keycloak-migrations'
            );
    }

     public function register()
     {
         Auth::extend('keycloak', function ($app, $name, array $config) {
             return new KeycloakGuard(Auth::createUserProvider($config['provider']), $app->request);
         });


     }

     protected function registerMigrations()
    {
        if ($this->app->runningInConsole() && Keycloak::$ignoreMigration === false) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }
}
