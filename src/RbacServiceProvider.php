<?php

namespace Zakirjarir\RbacAutomator;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Zakirjarir\RbacAutomator\Middleware\CheckPermission;
use Zakirjarir\RbacAutomator\Middleware\CheckModule;
use Zakirjarir\RbacAutomator\Console\Commands\InstallRbac;

class RbacServiceProvider extends ServiceProvider
{
    public function boot(Router $router): void
    {
        // Register Migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Register Config
        $this->publishes([
            __DIR__ . '/../config/rbac.php' => config_path('rbac.php'),
        ], 'rbac-config');

        // Register Middleware
        $router->aliasMiddleware('permission', CheckPermission::class);
        $router->aliasMiddleware('module', CheckModule::class);

        // Register Commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallRbac::class,
            ]);
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/rbac.php', 'rbac'
        );
    }
}
