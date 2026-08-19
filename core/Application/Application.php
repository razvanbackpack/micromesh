<?php

namespace Core\Application;

use Core\Helpers\Resource;
use Core\Helpers\Asset;
use Core\Http\Route;
use Core\Http\ErrorHandler;
use Core\Container\Container;

class Application 
{
    private static ?Container $container = null;

    public function __construct(?int $port = null)
    {
        if($port)
        {
            Asset::$PORT=$port;
            Resource::$PORT=$port;
        }

        // Initialize the container
        if (self::$container === null) {
            self::$container = new Container();
        }
    }

    public function Run()
    {
        ErrorHandler::register();
        $this->initializeContainer();
        $this->loadRoutes();
        DB::init();
    }

    /**
     * Get the application container.
     * 
     * @return Container
     */
    public static function getContainer(): Container
    {
        if (self::$container === null) {
            self::$container = new Container();
        }
        return self::$container;
    }

    /**
     * Initialize the container with default bindings.
     * 
     * Can be extended by applications to register services.
     * 
     * @return void
     */
    private function initializeContainer(): void
    {
        $container = self::getContainer();
        
        // Register the container itself
        $container->instance(Container::class, $container);
        
        // Register Request helper (if needed by services)
        // Services can inject Core\Helpers\Request via type hint
    }    

    public function Exit()
    {
        $result = Route::ValidateRoute();

        if ($result !== null) {
            echo $result;
        }

        exit();
    }


    private function loadRoutes()
    {
        Route::initiate();
        Route::RegisterRoutes(BASEDIR.'/routes/');
    }
}