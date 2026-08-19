<?php

namespace Tests\Unit\Container;

use App\Repositories\UserRepository;
use Core\Container\Container;
use Core\Container\Exceptions\CircularDependencyException;
use Core\Container\Exceptions\UnresolvableServiceException;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testItResolvesBoundClasses(): void
    {
        $this->container->bind('user.repository', UserRepository::class);

        $this->assertInstanceOf(
            UserRepository::class,
            $this->container->make('user.repository')
        );
    }

    public function testRegisteredInstanceIsReturnedUnchanged(): void
    {
        $repository = new UserRepository();
        $this->container->instance('user.repository', $repository);

        $this->assertSame($repository, $this->container->make('user.repository'));
    }

    public function testFactoryCreatesNewInstances(): void
    {
        $this->container->bind('user.repository', function (): UserRepository {
            return new UserRepository();
        });

        $this->assertNotSame(
            $this->container->make('user.repository'),
            $this->container->make('user.repository')
        );
    }

    public function testConcreteClassIsAutomaticallyResolved(): void
    {
        $this->assertInstanceOf(
            UserRepository::class,
            $this->container->make(UserRepository::class)
        );
    }

    public function testHasRecognizesBindingsAndResolvableClasses(): void
    {
        $this->container->bind('user.repository', UserRepository::class);

        $this->assertTrue($this->container->has('user.repository'));
        $this->assertTrue($this->container->has(UserRepository::class));
        $this->assertFalse($this->container->has('missing.service'));
    }

    public function testCircularDependenciesAreRejected(): void
    {
        $this->expectException(CircularDependencyException::class);

        $this->container->make(CircularDependencyA::class);
    }

    public function testUnresolvableServicesAreRejected(): void
    {
        $this->expectException(UnresolvableServiceException::class);

        $this->container->make('missing.service');
    }

    public function testCallResolvesTypedParameters(): void
    {
        $resolved = $this->container->call(
            fn(UserRepository $repository): UserRepository => $repository
        );

        $this->assertInstanceOf(UserRepository::class, $resolved);
    }
}

class CircularDependencyA
{
    public function __construct(CircularDependencyB $dependency)
    {
    }
}

class CircularDependencyB
{
    public function __construct(CircularDependencyA $dependency)
    {
    }
}
