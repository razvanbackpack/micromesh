<?php
namespace Core\Routing;

use Core\Helpers\Request;
use Closure;

class Route 
{
    public static array $ROUTES;

    // routes assigned with get
    public static function get(
        string $route_link,
        string|Closure $controller,
        string $function_call = null,
    )
    {
        self::$ROUTES[] = [
            'link' => $route_link,
            'class' => $controller,
            'function_call' => $function_call,
            'type' => 'GET',
        ];
    }

    // routes assigned with post
    public static function post(
        string $route_link,
        string $controller,
        string $function_call,
    )
    {
        self::$ROUTES[] = [
            'link' => $route_link,
            'class' => $controller,
            'function_call' => $function_call,
            'type' => 'POST',
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
    

            // TODO: figure out a way to add optional parameters. 
            // TODO: for now the code just checks if the params 
            // TODO: broken for the link are equal in number to 
            // TODO: the ones registered in the route
            
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

   
        if($controller_function===null)
            return call_user_func_array(
                $controller_class,
                $request_parameters
        );
        
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