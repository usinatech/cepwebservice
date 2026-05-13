<?php 

namespace UsinaTech\CEPWebservice;

use Illuminate\Support\ServiceProvider;

class CEPWebserviceServiceProvider extends ServiceProvider {

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
    }

}
