<?php

declare(strict_types=1);

namespace Tests\Core;

use App\Core\Container;
use App\Core\ContainerException;
use PHPUnit\Framework\TestCase;

final class ContainerTest extends TestCase
{
    public function testAutowiresNestedDependencies(): void
    {
        $service = (new Container())->get(ContainerTestService::class);

        self::assertInstanceOf(ContainerTestService::class, $service);
        self::assertInstanceOf(ContainerTestDep::class, $service->dep);
    }

    public function testAutowiredObjectsAreNotShared(): void
    {
        $container = new Container();

        self::assertNotSame($container->get(ContainerTestDep::class), $container->get(ContainerTestDep::class));
    }

    public function testSingletonReturnsSameInstance(): void
    {
        $container = new Container();
        $container->singleton(ContainerTestDep::class, fn (): ContainerTestDep => new ContainerTestDep());

        self::assertSame($container->get(ContainerTestDep::class), $container->get(ContainerTestDep::class));
    }

    public function testShareAutowiresButKeepsOneInstance(): void
    {
        $container = new Container();
        $container->share(ContainerTestService::class);

        self::assertSame($container->get(ContainerTestService::class), $container->get(ContainerTestService::class));
    }

    public function testBindCreatesNewInstanceEveryTime(): void
    {
        $container = new Container();
        $container->bind(ContainerTestDep::class, fn (): ContainerTestDep => new ContainerTestDep());

        self::assertNotSame($container->get(ContainerTestDep::class), $container->get(ContainerTestDep::class));
    }

    public function testRegisteredInstanceIsUsedForAutowiring(): void
    {
        $container = new Container();
        $dep = new ContainerTestDep();
        $container->instance(ContainerTestDep::class, $dep);

        self::assertSame($dep, $container->get(ContainerTestService::class)->dep);
    }

    public function testFactoryReceivesContainer(): void
    {
        $container = new Container();
        $container->bind(ContainerTestService::class, fn (Container $c): ContainerTestService => new ContainerTestService($c->get(ContainerTestDep::class)));

        self::assertInstanceOf(ContainerTestDep::class, $container->get(ContainerTestService::class)->dep);
    }

    public function testDefaultValuesAreUsedForScalars(): void
    {
        self::assertSame(5, (new Container())->get(ContainerTestWithDefault::class)->limit);
    }

    public function testScalarWithoutDefaultCannotBeResolved(): void
    {
        $this->expectException(ContainerException::class);

        (new Container())->get(ContainerTestNeedsScalar::class);
    }

    public function testCircularDependencyIsDetected(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Zirkuläre Abhängigkeit');

        (new Container())->get(ContainerTestCycleA::class);
    }

    public function testUnknownClassAndAbstractClassFail(): void
    {
        $container = new Container();

        try {
            $container->get('Does\\Not\\Exist');
            self::fail('Ausnahme erwartet');
        } catch (ContainerException) {
            self::addToAssertionCount(1);
        }

        $this->expectException(ContainerException::class);
        $container->get(ContainerTestAbstract::class);
    }
}

final class ContainerTestDep
{
}

final class ContainerTestService
{
    public function __construct(public readonly ContainerTestDep $dep)
    {
    }
}

final class ContainerTestWithDefault
{
    public function __construct(public readonly int $limit = 5)
    {
    }
}

final class ContainerTestNeedsScalar
{
    public function __construct(public readonly string $name)
    {
    }
}

final class ContainerTestCycleA
{
    public function __construct(public readonly ContainerTestCycleB $b)
    {
    }
}

final class ContainerTestCycleB
{
    public function __construct(public readonly ContainerTestCycleA $a)
    {
    }
}

abstract class ContainerTestAbstract
{
}
