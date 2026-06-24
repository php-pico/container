<?php

declare(strict_types=1);

namespace PhpPico\Container\Tests;

use PhpPico\Container\SimpleContainer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

#[CoversClass(ContainerInterface::class)]
#[CoversClass(SimpleContainer::class)]
final class SimpleContainerTest extends TestCase
{
    #[Test]
    public function implements_interface(): void
    {
        $container = new SimpleContainer();

        $expectedClass = ContainerInterface::class;
        $this->assertInstanceOf(
            $expectedClass,
            $container,
            sprintf('SimpleContainer must be an instance of %s', $expectedClass),
        );
    }

    #[Test]
    public function has_returns_false_on_not_found_service(): void
    {
        $container = new SimpleContainer();

        $this->assertFalse(
            $container->has('\NotFoundClass'),
            'SimpleContainer::has() must return FALSE if no service is registered for the provided ID',
        );
    }

    #[Test]
    public function has_returns_true_for_known_service(): void
    {
        $container = new SimpleContainer();

        $id = $container::class;
        $container->register($id, fn() => new $id());

        $this->assertTrue(
            $container->has($id),
            'SimpleContainer::has() must return TRUE if no service is registered for the provided ID',
        );
    }

    #[Test]
    public function get_returns_new_instance_of_service(): void
    {
        $container = new SimpleContainer();

        $id = $container::class;
        $container->register($id, fn() => new $id());

        /** @var SimpleContainer $service */
        $service = $container->get($id);
        $this->assertInstanceOf($id, $service, sprintf('SimpleContainer::has() should return an instance of %s', $id));
    }

    #[Test]
    public function get_throws_exception_for_unknown_service(): void
    {
        $container = new SimpleContainer();

        $id = $container::class;
        $this->expectException(NotFoundExceptionInterface::class);
        $container->get($id);
    }

    #[Test]
    public function same_singleton_instance_returned_for_singleton(): void
    {
        $container = new SimpleContainer();

        $id = $container::class;
        $container->registerSingleton($id, fn() => new $id());

        /** @var SimpleContainer $a */
        $a = $container->get($id);
        /** @var SimpleContainer $b */
        $b = $container->get($id);

        $this->assertSame(
            $a,
            $b,
            'SimpleContainer::registerSingleton() must wrap the invoker in a singleton handler, so the same instance is returned everytime.',
        );
    }
}
