<?php

namespace Core\Container;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;
use Core\Container\Exceptions\CircularDependencyException;
use Core\Container\Exceptions\UnresolvableServiceException;

/**
 * Lightweight dependency injection container.
 * 
 * Supports binding, singletons, instance registration, and automatic
 * resolution of constructor and method dependencies via reflection.
 */
class Container
{
    /** @var array<string, Closure|string|object> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $singletons = [];

    /** @var array<string, bool> */
    private array $resolving = [];

    /**
     * Register a binding in the container.
     * 
     * @param string $abstract The service name or interface
     * @param object|string $concrete Factory function, class name, or instance
     * @return void
     */
    public function bind(string $abstract, object|string $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Register a singleton binding.
     * 
     * The same instance will be returned on every resolution.
     * 
     * @param string $abstract The service name or interface
     * @param object|string $concrete Factory function, class name, or instance
     * @return void
     */
    public function singleton(string $abstract, object|string $concrete): void
    {
        $this->bind($abstract, $concrete);
        
        // If it's already instantiated, mark it as a singleton
        if (is_object($concrete) && !($concrete instanceof Closure)) {
            $this->singletons[$abstract] = $concrete;
        }
    }

    /**
     * Register an instance in the container.
     * 
     * @param string $abstract The service name
     * @param object $instance The instance to register
     * @return void
     */
    public function instance(string $abstract, object $instance): void
    {
        $this->singletons[$abstract] = $instance;
        $this->bindings[$abstract] = $instance;
    }

    /**
     * Resolve a service from the container.
     * 
     * Automatically resolves constructor dependencies using reflection.
     * Detects circular dependencies.
     * 
     * @param string $abstract The service name or fully-qualified class name
     * @param array<string, mixed> $parameters Override parameters by name
     * @return object The resolved instance
     * @throws CircularDependencyException If a circular dependency is detected
     * @throws UnresolvableServiceException If the service cannot be resolved
     */
    public function make(string $abstract, array $parameters = []): object
    {
        // Check for circular dependency
        if (isset($this->resolving[$abstract])) {
            throw new CircularDependencyException(
                "Circular dependency detected while resolving: {$abstract}"
            );
        }

        // Return singleton if already resolved
        if (isset($this->singletons[$abstract])) {
            return $this->singletons[$abstract];
        }

        // Mark as being resolved
        $this->resolving[$abstract] = true;

        try {
            $instance = $this->resolve($abstract, $parameters);

            // Cache singletons
            if (isset($this->bindings[$abstract])) {
                $binding = $this->bindings[$abstract];
                if (!($binding instanceof Closure) && is_string($binding) && $binding === $abstract) {
                    $this->singletons[$abstract] = $instance;
                }
            }

            return $instance;
        } finally {
            unset($this->resolving[$abstract]);
        }
    }

    /**
     * Check if a service is registered or resolvable.
     * 
     * @param string $abstract The service name or class name
     * @return bool True if the service can be resolved
     */
    public function has(string $abstract): bool
    {
        // Check if explicitly bound
        if (isset($this->bindings[$abstract])) {
            return true;
        }

        // Check if it's a registered singleton
        if (isset($this->singletons[$abstract])) {
            return true;
        }

        // Check if it's a class that can be auto-resolved
        try {
            return class_exists($abstract);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Resolve a service, handling factory closures and class instantiation.
     * 
     * @param string $abstract
     * @param array<string, mixed> $parameters
     * @return object
     * @throws UnresolvableServiceException
     */
    private function resolve(string $abstract, array $parameters = []): object
    {
        // If there's an explicit binding
        if (isset($this->bindings[$abstract])) {
            $binding = $this->bindings[$abstract];

            // If it's a Closure, invoke it
            if ($binding instanceof Closure) {
                return $binding($this, $parameters);
            }

            // If it's an instance, return it
            if (is_object($binding) && !is_string($binding)) {
                return $binding;
            }

            // If it's a string (class name), resolve that class
            if (is_string($binding)) {
                return $this->resolveClass($binding, $parameters);
            }
        }

        // Try to auto-resolve as a concrete class
        if (class_exists($abstract)) {
            return $this->resolveClass($abstract, $parameters);
        }

        throw new UnresolvableServiceException(
            "Cannot resolve service: {$abstract}"
        );
    }

    /**
     * Resolve a class by instantiating it with constructor dependency injection.
     * 
     * @param string $className
     * @param array<string, mixed> $parameters
     * @return object
     * @throws UnresolvableServiceException
     */
    private function resolveClass(string $className, array $parameters = []): object
    {
        if (!class_exists($className)) {
            throw new UnresolvableServiceException(
                "Class does not exist: {$className}"
            );
        }

        $reflection = new ReflectionClass($className);

        // Check if class is instantiable
        if (!$reflection->isInstantiable()) {
            throw new UnresolvableServiceException(
                "Class is not instantiable: {$className}"
            );
        }

        $constructor = $reflection->getConstructor();

        // No constructor, instantiate directly
        if ($constructor === null) {
            return new $className();
        }

        // Resolve constructor parameters
        $arguments = $this->resolveMethodParameters($constructor, $parameters);

        return $reflection->newInstanceArgs($arguments);
    }

    /**
     * Resolve method/function parameters using type hints and parameter names.
     * 
     * Prioritizes explicit parameters, then type-hinted dependencies, then positional.
     * 
     * @param ReflectionMethod|ReflectionFunction $reflection
     * @param array<string, mixed> $parameters
     * @return array<mixed>
     * @throws UnresolvableServiceException
     */
    public function resolveMethodParameters(
        ReflectionMethod|ReflectionFunction $reflection,
        array $parameters = []
    ): array {
        $arguments = [];

        foreach ($reflection->getParameters() as $param) {
            $paramName = $param->getName();

            // 1. Check explicit parameters first
            if (array_key_exists($paramName, $parameters)) {
                $arguments[] = $parameters[$paramName];
                continue;
            }

            // 2. Try to resolve by type hint
            $type = $param->getType();
            if ($type !== null && $type instanceof \ReflectionNamedType) {
                $typeName = $type->getName();

                // Check if it's a built-in type
                if (in_array($typeName, ['string', 'int', 'float', 'bool', 'array'], true)) {
                    // Built-in type with no default and no parameter provided
                    if (!$param->isDefaultValueAvailable()) {
                        throw new UnresolvableServiceException(
                            "Cannot resolve built-in type parameter: {$paramName} ({$typeName})"
                        );
                    }
                    $arguments[] = $param->getDefaultValue();
                    continue;
                }

                // Try to resolve as a service
                if ($this->has($typeName)) {
                    $arguments[] = $this->make($typeName);
                    continue;
                }
            }

            // 3. Use default value if available
            if ($param->isDefaultValueAvailable()) {
                $arguments[] = $param->getDefaultValue();
                continue;
            }

            // 4. Unable to resolve
            throw new UnresolvableServiceException(
                "Cannot resolve parameter: {$paramName}"
            );
        }

        return $arguments;
    }

    /**
     * Invoke a callable with dependency resolution.
     * 
     * @param callable $callback Closure or [Object, 'method']
     * @param array<string, mixed> $parameters Override parameters
     * @return mixed The return value of the callback
     * @throws UnresolvableServiceException
     */
    public function call(callable $callback, array $parameters = []): mixed
    {
        if (is_array($callback)) {
            $reflection = new ReflectionMethod($callback[0], $callback[1]);
        } else {
            $reflection = new ReflectionFunction(\Closure::fromCallable($callback));
        }

        $arguments = $this->resolveMethodParameters($reflection, $parameters);

        return $callback(...$arguments);
    }
}
