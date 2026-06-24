<?php

declare(strict_types=1);

namespace PhpPico\Container\Tests;

use PhpPico\Container\AutowiringContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

#[CoversClass(ContainerInterface::class)]
#[CoversClass(AutowiringContainer::class)]
final class AutowiringContainerTest extends TestCase
{
    #[Test]
    public function implements_interface(): void
    {
        $container = new AutowiringContainer();

        $expectedClass = ContainerInterface::class;
        $this->assertInstanceOf(
            $expectedClass,
            $container,
            sprintf('AutowiringContainer must be an instance of %s', $expectedClass),
        );
    }

    #[Test]
    public function has_returns_false_on_nonexistent_class(): void
    {
        $container = new AutowiringContainer();

        $this->assertFalse(
            $container->has('\NotFound'),
            'AutowiringContainer::has() must return FALSE when trying to resolve a non existent class',
        );
    }

    #[Test]
    public function has_returns_true_if_service_is_registered(): void
    {
        $container = new AutowiringContainer();

        $id = $container::class;
        $container->register($id, fn() => new $id());

        $this->assertTrue(
            $container->has($id),
            'AutowiringContainer::has() must return TRUE when passed a registered service',
        );
    }

    #[Test]
    public function get_throws_exception_when_trying_to_resolve_nonexistent_class(): void
    {
        $container = new AutowiringContainer();

        $this->expectException(ContainerExceptionInterface::class);
        $container->get('\NotFound');
    }

    #[Test]
    public function get_registered_service(): void
    {
        $container = new AutowiringContainer();

        $id = $container::class;
        $container->register($id, fn() => new $id());

        $this->assertInstanceOf(
            $id,
            $container->get($id),
            'AutowiringContainer::get() must return an intance of the registered service',
        );
    }

    #[Test]
    public function auto_resolve_service_by_class_name(): void
    {
        $container = new AutowiringContainer();

        $id = $container::class;
        $this->assertFalse(
            $container->has($id),
            'AutowiringContainer::has() must return FALSE for the auto-resolved service since it\'s not registered',
        );

        // @mago-expect analysis:mixed-assignment
        $a = $container->get($id);
        $this->assertInstanceOf($id, $a, 'AutowiringContainer::get() must auto-resolve a class by it\'s FQN');
    }
}
