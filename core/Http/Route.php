<?php
namespace Core\Http;

use Core\Helpers\Request;
use Core\Helpers\Config;
use Core\Helpers\Log;
use Core\Http\Response;
use Core\Http\Cors;
use Core\Application\Application;
use Core\Container\Container;
use Core\Container\Exceptions\UnresolvableServiceException;
use ReflectionMethod;
use ReflectionFunction;

use Closure;
use Throwable;

class Route
{
    public static array $ROUTES = [];
    private static array $GLOBAL_MIDDLEWARE = [];
    private static $notFoundHandler;
    private static string $prefix = '';
    private static array $ALLOWED_METHODS =[];


    public static function initiate() 
    {
        $http_config = Config::get('http');

        self::$ALLOWED_METHODS = $http_config['allowed_methods'];
    }
    public static function RegisterRoutes($basedir)
    {
        $route_files = Config::get('routes');
       
        foreach ($route_files as $route_file) {
            $file = $basedir . $route_file['file'];
            if (!file_exists($file)) {
                continue;
            }

            self::$prefix = $route_file['prefix'] . '/';
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

    public static function put($path, $handler, $middleware = []): void
    {
        self::addRoute('PUT', $path, $handler, $middleware);
    }

    public static function patch($path, $handler, $middleware = []): void
    {
        self::addRoute('PATCH', $path, $handler, $middleware);
    }

    public static function delete($path, $handler, $middleware = []): void
    {
        self::addRoute('DELETE', $path, $handler, $middleware);
    }

    public static function addGlobalMiddleware($middleware)
    {
        self::$GLOBAL_MIDDLEWARE[] = $middleware;
    }

    public function setNotFoundHandler($handler)
    {
        self::$notFoundHandler = $handler;
    }

    public static function ValidateRoute()
    {
        (new Cors())->handleCors();
        $requestObject = Request::capture();
        $request = Request::$REQUEST_DATA;

        $uri = self::sanitizeUri($request['link']);
        $method = self::sanitizeMethod($request['method']);

        if (!in_array($method, self::$ALLOWED_METHODS)) {
            return self::error(405, 'Method Not Allowed');
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
        foreach (self::$GLOBAL_MIDDLEWARE as $middleware) {
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
            return self::Next($matchedRoute['handler'], $params, $requestObject);
        } catch (Throwable $e) {
            return self::error(500, 'Internal Server Error', $e->getMessage());
        }
    }

    private static function Next(mixed $handler, array $params, Request $request): mixed
    {
        try {
            $container = Application::getContainer();

            if (is_callable($handler)) {
                return self::invoke($handler, $params, $request, $container);
            } elseif (is_array($handler) && count($handler) == 2) {
                [$class, $method] = $handler;
                if (is_string($class)) {
                    // Use container to instantiate controller with constructor DI
                    $controller = $container->make($class);
                } else {
                    $controller = $class;
                }
                return self::invoke([$controller, $method], $params, $request, $container);
            } else {
                throw new \RuntimeException('Invalid route handler');
            }
        } catch (Throwable $e) {
            return self::error(500, 'Internal Server Error', $e->getMessage());
        }
    }

    private static function invoke(callable $handler, array $params, Request $request, Container $container): mixed
    {
        $reflection = is_array($handler)
            ? new ReflectionMethod($handler[0], $handler[1])
            : new ReflectionFunction(Closure::fromCallable($handler));
        $arguments = [];
        $positional = array_values($params);
        $paramIndex = 0;

        foreach ($reflection->getParameters() as $parameter) {
            $paramName = $parameter->getName();
            $type = $parameter->getType();

            // 1. Special handling for Request type - inject the request object
            if ($type instanceof \ReflectionNamedType
                && $type->getName() === Request::class) {
                $arguments[] = $request;
                continue;
            }

            // 2. Try to resolve from explicit route parameters first
            if (array_key_exists($paramName, $params)) {
                $arguments[] = $params[$paramName];
                continue;
            }

            // 3. Try to resolve from positional route parameters
            if (array_key_exists($paramIndex, $positional)) {
                $arguments[] = $positional[$paramIndex++];
                continue;
            }

            // 4. Try to resolve from container by type hint
            if ($type instanceof \ReflectionNamedType) {
                $typeName = $type->getName();
                
                // Skip built-in types
                if (!in_array($typeName, ['string', 'int', 'float', 'bool', 'array', 'mixed'], true)) {
                    if ($container->has($typeName)) {
                        try {
                            $arguments[] = $container->make($typeName);
                            continue;
                        } catch (UnresolvableServiceException) {
                            // Fall through to default handling
                        }
                    }
                }
            }

            // 5. Use default value if available
            if ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
                continue;
            }

            // 6. Unresolvable parameter - this may be intentional for route params
            // that weren't matched. The old behavior accepted this, so we continue.
        }

        return $handler(...$arguments);
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

    /**
     * Execute middleware and handle the response
     * 
     * Middleware contract:
     * - Return `null` to pass and continue to next middleware
     * - Return `false` to reject with 403 Forbidden
     * - Return any other value to short-circuit and return that response
     * - Throw an exception to reject with 500 error
     * 
     * @param Closure|callable $middleware The middleware function
     * @param array $params Route parameters to pass to middleware
     * @return string|null Response on rejection, null on pass
     */
    private static function executeMiddleware($middleware, $params): ?string
    {
        try {
            $result = call_user_func_array($middleware, $params);
            
            // Middleware passed - continue to next middleware
            if ($result === null) {
                return null;
            }
            
            // Middleware rejected with standard forbidden
            if ($result === false) {
                return self::error(403, 'Forbidden');
            }
            
            // Middleware returned a custom response - short-circuit
            return $result;
        } catch (Throwable $e) {
            return self::error(500, 'Middleware Error', $e->getMessage());
        }
    }

    private static function handleNotFound()
    {
        if (self::$notFoundHandler) {
            return call_user_func(self::$notFoundHandler);
        }
        return self::error(404, 'Not Found');
    }

    public static function error($code, $message, $details = '')
    {
        http_response_code($code);
        Log::error($message, ['code' => $code, 'details' => $details]);
        return ErrorHandler::response($code, $message, $details);
    }
}
