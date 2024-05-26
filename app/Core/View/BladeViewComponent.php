<?php
namespace App\Core\View;

use eftec\bladeone\BladeOne;

trait BladeViewComponent
{ 
    function view(string $view_name = "", array $parameters = [])
    {
        $VIEWS_FOLDER = 'views';
        $CACHE_FOLDER = 'cache';

        $blade = new BladeOne($VIEWS_FOLDER, $CACHE_FOLDER);

        echo $blade->run($view_name, $parameters);
        
    }
}