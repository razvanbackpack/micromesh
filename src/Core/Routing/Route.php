<?php
namespace App\Core\Routing;

use App\Controllers;
use App\Helpers\Request;

class Route 
{
    public static array $ROUTES;

    public static function GET(
        string $route_link,
        string $controller,
        string $function_call,
    )
    {
        self::$ROUTES[] = [
            'link' => $route_link,
            'class' => $controller,
            'function_call' => $function_call,
            'type' => 'GET',
        ];
    }

    public static function ValidateRoute()
    {
        Request::CaptureRequest();
        $request = Request::$REQUEST_DATA;
        $request_parameters = [];

        foreach(self::$ROUTES as $registered_route)
        {
            $next_route = false;
            $request_link_parts = explode('/', $request['link']);
            $route_link_parts = explode('/', $registered_route['link']);
           
            if($request['type'] != $registered_route['type'])
                continue;

            if($request['link'] == $registered_route['link'])
                return self::Next($registered_route, $request, $request_parameters);
    
            if(count($request_link_parts) === count($route_link_parts))
            {
                foreach($route_link_parts as $route_link_part_key => $route_link_part)
                {
                    if(preg_match('/\{([^*]*)\}/', $route_link_part))
                    {

                        $request_parameters[] = $request_link_parts[$route_link_part_key];
                    }
                    else 
                    {
                        if($request_link_parts[$route_link_part_key] != $route_link_part) 
                        {
                            $next_route = true;
                            break;
                        }
                       
                    }
                }
               
                if($next_route) continue;
           
                return self::Next($registered_route, $request, $request_parameters);
            }
            else
                continue;   

        }

        return self::FourOhFour();
    }

    public static function Next($registered_route, $request_data, $request_parameters)
    {
        $controller_class = $registered_route['class'];
        $controller_function = $registered_route['function_call'];
        $Controller = new $controller_class;
        $result2 = [];

        return call_user_func_array(
            [
                new $controller_class, 
                $controller_function
            ],
            $request_parameters 
        );
    }

    public static function FourOhFour()
    {
        dd('404');
    }
}