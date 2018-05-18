<?php

namespace TPenaranda\BCoin;

use Illuminate\Support\ServiceProvider;

class BCoinServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        return $this->publishes([
            __DIR__ . '/config/bcoin.php' => config_path('bcoin.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('tpenaranda-bcoin-laravel', function ($app) {
            return new BCoin;
        });
    }
}
