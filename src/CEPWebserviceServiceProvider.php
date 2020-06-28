<?php 

namespace UsinaHUB\CEPWebservice;

use Illuminate\Support\ServiceProvider;

class CEPWebserviceServiceProvider extends ServiceProvider {

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
    }

    public function register()
    {
        $this->app->make('UsinaHUB\CEPWebservice\CEPWebserviceController');
    }

}
