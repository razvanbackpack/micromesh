<?php
namespace Core\Routing;

use Core\Helpers\Request;
use Core\Helpers\Config;
use Core\Http\Response;

use Closure;
use Exception;

class Route
{
    public static array $ROUTES = [];
    private static array $globalMiddleware = [];
    private static $notFoundHandler;
    private static string $prefix = '';
    private static bool $check_ref = false;
    private static array $allowed_headers = [];

    public static function RegisterRoutes($basedir)
    {
        $route_files = Config::get('routes');
        foreach ($route_files as $route_file) {
            $file = $basedir . $route_file['file'];
            if (!file_exists($file)) {
                continue;
            }

            self::$prefix = $route_file['prefix'] . '/';
            self::$check_ref = $route_file['check_referrer'] ?? false;
            self::$allowed_headers = $route_file['allowed_headers'] ?? [];
            require $file;
        }
    }

    public static function addRoute($method, $path, $handler, $middleware = [])
    {
        self::$ROUTES[] = [
            'method' => $method,
            'path' => self::$prefix . ltrim($path, '/'),
            'handler' => $handler,
            'middleware' => $middleware,
            'check_referrer' => self::$check_ref,
            'allowed_headers' => self::$allowed_headers,
        ];
    }

    public static function get($path, $handler, $middleware = [])
    {
        self::addRoute('GET', $path, $handler, $middleware);
    }

    public static function post($path, $handler, $middleware = [])
    {
        self::addRoute('POST', $path, $handler, $middleware);
    }

    public function addGlobalMiddleware($middleware)
    {
        self::$globalMiddleware[] = $middleware;
    }

    public function setNotFoundHandler($handler)
    {
        self::$notFoundHandler = $handler;
    }

    public static function ValidateRoute()
    {
        Request::CaptureRequest();
        $request = Request::$REQUEST_DATA;

        
        // TODO: handle check_referrer and allowed_headers from routes.php config
        dd(self::$ROUTES,self::checkReferer(), $request);
        $uri = self::sanitizeUri($request['link']);
        $method = self::sanitizeMethod($request['method']);

        if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'])) {
            return self::respondWithError(405, 'Method Not Allowed');
        }

        $matchedRoute = null;
        $params = [];

        foreach (self::$ROUTES as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = self::convertRouteToRegex($route['path']);

            if (preg_match($pattern, $uri, $matches)) {
                $matchedRoute = $route;
                $params = array_filter(
                    $matches,
                    function ($key) use ($matches) {
                        return is_string($key) && $matches[$key] !== '';
                    },
                    ARRAY_FILTER_USE_KEY,
                );
                break;
            }
        }

        if ($matchedRoute === null) {
            return self::handleNotFound();
        }
        // Apply global middleware
        foreach (self::$globalMiddleware as $middleware) {
            $result = self::executeMiddleware($middleware, $params);
            if ($result !== null) {
                return $result;
            }
        }

        // Apply route-specific middleware
        foreach ($matchedRoute['middleware'] as $middleware) {
            $result = self::executeMiddleware($middleware, $params);
            if ($result !== null) {
                return $result;
            }
        }

        try {
            return self::Next($matchedRoute['handler'], $params);
        } catch (Exception $e) {
            return self::respondWithError(500, 'Internal Server Error', $e->getMessage());
        }
    }

    private static function Next($handler, $params)
    {
        try {
            if (is_callable($handler)) {
                return call_user_func_array($handler, $params);
            } elseif (is_array($handler) && count($handler) == 2) {
                [$class, $method] = $handler;
                if (is_string($class)) {
                    $controller = new $class();
                } else {
                    $controller = $class;
                }
                return $controller->$method(...$params);
            } else {
                throw new Exception('Invalid route handler');
            }
        } catch (Exception $e) {
            return self::respondWithError(500, 'Internal Server Error', $e->getMessage());
        }
    }

    private static function convertRouteToRegex($route)
    {
        $route = trim($route, '/');
        $routeParts = explode('/', $route);
        $pattern = [];

        foreach ($routeParts as $part) {
            if (strpos($part, '{') !== false) {
                $paramName = trim($part, '{}');
                if (substr($paramName, -1) === '?') {
                    $paramName = rtrim($paramName, '?');
                    $pattern[] = "(?:\/(?P<$paramName>[^\/]+))?";
                } else {
                    $pattern[] = "\/(?P<$paramName>[^\/]+)";
                }
            } else {
                $pattern[] = '\/' . preg_quote($part);
            }
        }

        // Ensure the last part is truly optional
        $lastPart = end($pattern);
        if (strpos($lastPart, ')?') !== false) {
            array_pop($pattern);
            $pattern[] = $lastPart . '?';
        }

        return '/^' . implode('', $pattern) . '\/?$/';
    }

    private static function sanitizeMethod($method)
    {
        return strtoupper(trim($method));
    }

    private static function sanitizeUri($uri)
    {
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }
        $uri = filter_var($uri, FILTER_SANITIZE_URL);
        return $uri !== false ? $uri : '';
    }

    private static function executeMiddleware($middleware, $params)
    {
        try {
            $result = call_user_func_array($middleware, $params);
            if ($result === false) {
                return self::respondWithError(403, 'Forbidden');
            }
            return $result;
        } catch (Exception $e) {
            return self::respondWithError(500, 'Middleware Error', $e->getMessage());
        }
    }

    private static function handleNotFound()
    {
        if (self::$notFoundHandler) {
            return call_user_func(self::$notFoundHandler);
        }
        return self::respondWithError(404, 'Not Found');
    }

    private static function respondWithError($code, $message, $details = '')
    {
        http_response_code($code);
        return json_encode([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ]);
    }

    private static function checkReferer()
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $allowedDomain = $_SERVER['APP_URL'];
        return strpos($referer, $allowedDomain) === 0;
    }

    private static function checkHeaders($route_headers, $request_headers)
    {

    }
}
