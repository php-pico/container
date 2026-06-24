<?php

declare(strict_types=1);

namespace PhpPico\Container;

use ArrayObject;
use Override;
use PhpPico\Container\Exceptions\ContainerException;
use PhpPico\Container\Exceptions\NotFoundException;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;

final readonly class AutowiringContainer implements ContainerInterface
{
    /** @param ArrayObject<string|class-string, callable(): mixed> $invokers */
    protected ArrayObject $invokers;

    public function __construct()
    {
        $this->invokers = new ArrayObject([]);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function get(string $id)
    {
        if ($this->has($id)) {
            /** @var callable(): mixed $invoker */
            $invoker = $this->invokers->offsetGet($id);

            return $invoker();
        }

        $invoker = $this->resolve($id);

        if (is_callable($invoker)) {
            $this->register($id, $invoker);

            return $invoker();
        }

        throw new ContainerException(sprintf('Failed to resolve service for ID %s', $id));
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function has(string $id): bool
    {
        return $this->invokers->offsetExists($id);
    }

    /**
     * Resolve a service by ID.
     *
     * @param string|class-string $id
     *
     * @return null|callable(): mixed
     * @throws ContainerException
     */
    public function resolve(string $id): ?callable
    {
        if (!class_exists($id)) {
            return null;
        }

        try {
            $reflectionClass = new ReflectionClass($id);

            if (!$reflectionClass->isInstantiable()) {
                return null;
            }

            $constructor = $reflectionClass->getConstructor();

            if (!$constructor) {
                return fn() => $reflectionClass->newInstance();
            }

            $dependencies = array_map(function ($parameter) {
                return (string) $parameter->getType();
            }, $constructor->getParameters());

            return function () use ($reflectionClass, $dependencies) {
                $args = array_map([$this, 'get'], $dependencies);

                return $reflectionClass->newInstanceArgs($args);
            };
        } catch (ReflectionException $e) {
            throw new ContainerException(sprintf('Failed to resolve service %s. Error: %s', $id, $e->getMessage()));
        }
    }

    /**
     * Register a service. The service is lazily-constructed.
     *
     * @param string            $id
     * @param callable(): mixed $callable
     *
     * @return self
     */
    public function register(string $id, $callable): self
    {
        $this->invokers->offsetSet($id, $callable);

        return $this;
    }

    /**
     * Register a service as a singleton. The service will be lazily-constructed.
     *
     * @param string            $id
     * @param callable(): mixed $callable
     *
     * @return self
     */
    public function registerSingleton(string $id, $callable): self
    {
        /**
         * @param string            $id
         * @param callable(): mixed $callable
         *
         * @return object
         */
        $invoker = function () use ($id, $callable) {
            static $service;

            if (!$service) {
                // @mago-expect analysis:mixed-assignment
                $service = $callable();
            }

            return $service;
        };

        return $this->register($id, $invoker);
    }
}
