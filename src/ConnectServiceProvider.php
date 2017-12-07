<?php

namespace Square1\Laravel\Connect;

use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Response;
use Square1\Laravel\Connect\Console\MakeClient;
use Square1\Laravel\Connect\Console\InitClient;
use Square1\Laravel\Connect\Console\InstallClient;
use Square1\Laravel\Connect\App\Middleware\AfterConnectMiddleware;

class ConnectServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        
        //registering 3rd party service providers
    
       //laravel passport, no need to register routes for this
       $this->app->register('Laravel\Passport\PassportServiceProvider');
      

        if ($this->app->runningInConsole()) {
            $this->loadViewsFrom(__DIR__ . '/views/client/android', 'android');
            $this->loadViewsFrom(__DIR__ . '/views/client/iOS', 'ios');
             
            $this->commands([
               MakeClient::class,
               InitClient::class,
               InstallClient::class
            ]);
        }
        
        Response::macro('connect', function ($value, $status = 200) {
            return Response::json([
                'data' => $value,
            ], $status);
        });
        
        //Registering routes
        //Passport::routes(null, ['prefix' => config('connect.api.prefix').'/passport']);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        app('router')->aliasMiddleware('connect', AfterConnectMiddleware::class);
        
        $this->loadRoutesFrom(__DIR__ . '/App/Routes/routes_connect.php');
    }
    
    /**
     * Load the standard routes file for the application.
     *
     * @param  string  $path
     * @return mixed
     */
    protected function loadRoutesFrom($path)
    {
        Route::group([
            'middleware' => ['api','connect'],
            'namespace' => 'Square1\Laravel\Connect\App\Http\Controllers',
            'prefix' => config('connect.api.prefix'),
             'as' => 'connect.'
        ], function ($router) use ($path) {
            require $path;
        });
    }
}
