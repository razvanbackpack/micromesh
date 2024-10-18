<?php

namespace Core\Application;

use Core\Helpers\Resource;
use Core\Helpers\Asset;
use Core\Http\Route;

class Application 
{
    public function __construct(?int $port = null)
    {
        if($port)
        {
            Asset::$PORT=$port;
            Resource::$PORT=$port;
        }
    }

    public function Run()
    {
        $this->loadRoutes();
        DB::init();
    }    

    public function Exit()
    {
        Route::ValidateRoute();
        exit();
    }


    private function loadRoutes()
    {
        Route::initiate();
        Route::RegisterRoutes(BASEDIR.'/routes/');
    }
}