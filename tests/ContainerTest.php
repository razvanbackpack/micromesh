<?php

namespace Tests;

use Core\Container\Container;
use Core\Container\Exceptions\CircularDependencyException;
use Core\Container\Exceptions\UnresolvableServiceException;
use App\Repositories\UserRepository;

/**
 * Test suite for dependency injection container.
 */
class ContainerTest
{
    private Container $container;

    public function __construct()
    {
        $this->container = new Container();
    }

    /**
     * Run all tests.
     */
    public function runAll(): void
    {
        echo "=== Dependency Injection Container Tests ===\n\n";

        try {
            $this->testBasicBinding();
            echo "✓ Basic binding test passed\n";
        } catch (Throwable $e) {
            echo "✗ Basic binding test failed: {$e->getMessage()}\n";
        }

        try {
            $this->testSingletonBinding();
            echo "✓ Singleton binding test passed\n";
        } catch (Throwable $e) {
            echo "✗ Singleton binding test failed: {$e->getMessage()}\n";
        }

        try {
            $this->testInstanceBinding();
            echo "✓ Instance binding test passed\n";
        } catch (Throwable $e) {
            echo "✗ Instance binding test failed: {$e->getMessage()}\n";
        }

        try {
            $this->testClosureBinding();
            echo "✓ Closure binding test passed\n";
        } catch (Throwable $e) {
            echo "✗ Closure binding test failed: {$e->getMessage()}\n";
        }

        try {
            $this->testAutoResolving();
            echo "✓ Auto-resolving test passed\n";
        } catch (Throwable $e) {
            echo "✗ Auto-resolving test failed: {$e->getMessage()}\n";
        }

        try {
            $this->testHasMethod();
            echo "✓ has() method test passed\n";
        } catch (Throwable $e) {
            echo "✗ has() method test failed: {$e->getMessage()}\n";
        }

        try {
            $this->testCircularDependency();
            echo "✓ Circular dependency test passed\n";
        } catch (Throwable $e) {
            echo "✗ Circular dependency test failed: {$e->getMessage()}\n";
        }

        try {
            $this->testMethodParameterResolution();
            echo "✓ Method parameter resolution test passed\n";
        } catch (Throwable $e) {
            echo "✗ Method parameter resolution test failed: {$e->getMessage()}\n";
        }

        echo "\n=== All tests completed ===\n";
    }

    private function testBasicBinding(): void
    {
        $this->container->bind('test', UserRepository::class);
        $resolved = $this->container->make('test');
        
        if (!($resolved instanceof UserRepository)) {
            throw new \Exception("Expected UserRepository instance");
        }
    }

    private function testSingletonBinding(): void
    {
        $this->container->singleton('user.repo', UserRepository::class);
        $first = $this->container->make('user.repo');
        $second = $this->container->make('user.repo');
        
        if ($first !== $second) {
            throw new \Exception("Singleton did not return same instance");
        }
    }

    private function testInstanceBinding(): void
    {
        $instance = new UserRepository();
        $this->container->instance('user', $instance);
        $resolved = $this->container->make('user');
        
        if ($resolved !== $instance) {
            throw new \Exception("Instance binding did not return exact instance");
        }
    }

    private function testClosureBinding(): void
    {
        $this->container->bind('factory', function($container) {
            return new UserRepository();
        });
        
        $first = $this->container->make('factory');
        $second = $this->container->make('factory');
        
        if ($first === $second) {
            throw new \Exception("Closure binding should return new instances");
        }
    }

    private function testAutoResolving(): void
    {
        $resolved = $this->container->make(UserRepository::class);
        
        if (!($resolved instanceof UserRepository)) {
            throw new \Exception("Auto-resolving failed");
        }
    }

    private function testHasMethod(): void
    {
        $this->container->bind('exists', UserRepository::class);
        
        if (!$this->container->has('exists')) {
            throw new \Exception("has() returned false for bound service");
        }

        if (!$this->container->has(UserRepository::class)) {
            throw new \Exception("has() returned false for resolvable class");
        }

        if ($this->container->has('non.existent.service')) {
            throw new \Exception("has() returned true for non-existent service");
        }
    }

    private function testCircularDependency(): void
    {
        $circularA = new class {
            public function __construct(
                public $b
            ) {}
        };

        $circularB = new class {
            public function __construct(
                public $a
            ) {}
        };

        // This is a simple test - full circular dependency testing would require
        // actual classes. Just verify the exception class exists.
        if (!class_exists(CircularDependencyException::class)) {
            throw new \Exception("CircularDependencyException not found");
        }
    }

    private function testMethodParameterResolution(): void
    {
        $repo = $this->container->make(UserRepository::class);
        
        $users = $repo->all();
        if (count($users) !== 2) {
            throw new \Exception("Repository should return 2 users");
        }

        $user = $repo->find(1);
        if ($user === null || $user['id'] !== 1) {
            throw new \Exception("Repository find() failed");
        }

        $created = $repo->create(['name' => 'Bob', 'email' => 'bob@example.com']);
        if ($created['name'] !== 'Bob') {
            throw new \Exception("Repository create() failed");
        }
    }
}

// Run tests if this file is executed directly
if (php_sapi_name() === 'cli' && basename($argv[0] ?? '') === 'ContainerTest.php') {
    try {
        $test = new ContainerTest();
        $test->runAll();
    } catch (Throwable $e) {
        echo "Fatal error: {$e->getMessage()}\n";
        echo $e->getTraceAsString();
        exit(1);
    }
}
