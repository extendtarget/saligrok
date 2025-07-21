<?php

namespace Modules\ManageOrder\Providers;
//custom
if (!defined('STDIN')) define('STDIN',fopen("php://stdin","r"));

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;

//Traits
use Modules\ManageOrder\Traits\AdminCodeTrait;
use Modules\ManageOrder\Traits\OperationTrait;

class ManageOrderServiceProvider extends ServiceProvider
{
    //use Traits
    use AdminCodeTrait, OperationTrait;
    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(module_path('ManageOrder', 'Database/Migrations'));
        //custom traits
        self::runAdminScript();
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            module_path('ManageOrder', 'Config/config.php') => config_path('manageorder.php'),
        ], 'config');
        $this->mergeConfigFrom(
            module_path('ManageOrder', 'Config/config.php'), 'manageorder'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/manageorder');

        $sourcePath = module_path('ManageOrder', 'Resources/views');

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/manageorder';
        }, \Config::get('view.paths')), [$sourcePath]), 'manageorder');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $langPath = resource_path('lang/modules/manageorder');

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, 'manageorder');
        } else {
            $this->loadTranslationsFrom(module_path('ManageOrder', 'Resources/lang'), 'manageorder');
        }
    }

    /**
     * Register an additional directory of factories.
     *
     * @return void
     */
    public function registerFactories()
    {
        if (! app()->environment('production') && $this->app->runningInConsole()) {
            app(Factory::class)->load(module_path('ManageOrder', 'Database/factories'));
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
