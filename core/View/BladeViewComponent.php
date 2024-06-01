<?php
namespace Core\View;

use Core\Config\Config;
use Core\Resource\Resource;
use eftec\bladeone\BladeOne;

trait BladeViewComponent
{ 
    function view(string $view_name = "", array $parameters = [])
    {
        $VIEWS_FOLDER = BASEDIR.'/resources/views';
        $CACHE_FOLDER = BASEDIR.'/cache';

        $blade = new BladeOne($VIEWS_FOLDER, $CACHE_FOLDER);
        
        $this->setCustomDirectives($blade);

        echo $blade->run($view_name, $parameters);
        return;
    }


    function setCustomDirectives(BladeOne &$blade)
    {
        $blade->directiveRT('config', function($parameters)
        {
            echo Config::get($parameters);
        });


        $blade->directiveRT('resource', function($parameters)
        {
            echo Resource::get($parameters);
        });
    }
}