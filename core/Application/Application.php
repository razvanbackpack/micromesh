<?php

namespace Core\Application;
use Core\Routing\Route;

class Application 
{
    public function __construct()
    {
        $this->loadRoutes();
    }

    public function Run()
    {
        // legit don't know what to do with this atm.
        // left this here anyway
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