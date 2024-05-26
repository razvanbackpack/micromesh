<?php

namespace Core\Application;
use Core\Routing\Route;

class Application 
{
    public function __construct()
    {
        
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
        require_once BASEDIR.'/routes/web.php';
    }
}