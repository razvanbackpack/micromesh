<?php
namespace Core\View;

use Core\Config\Config;
use eftec\bladeone\BladeOne;

trait BladeViewComponent
{ 
    function view(string $view_name = "", array $parameters = [])
    {
        $VIEWS_FOLDER = 'views';
        $CACHE_FOLDER = 'cache';

        $blade = new BladeOne($VIEWS_FOLDER, $CACHE_FOLDER);
        
        $this->setCustomDirectives($blade);

        echo $blade->run($view_name, $parameters);
        return;
    }


    function setCustomDirectives(BladeOne &$blade)
    {
        $blade->directiveRT('Config', function($parameters)
        {
            echo Config::get($parameters);
        });
    }
}