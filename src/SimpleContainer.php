<?php

declare(strict_types=1);

namespace PhpPico\Container;

use ArrayObject;
use Override;
use PhpPico\Container\Exceptions\NotFoundException;
use Psr\Container\ContainerInterface;

final readonly class SimpleContainer implements ContainerInterface
{
    /**
     * @param ArrayObject<class-string, callable(): mixed> $invokers
     */
    public function __construct(
        protected ArrayObject $invokers = new ArrayObject([]),
    ) {}

    /**
     * @inheritDoc
     */
    #[Override]
    public function get(string $id)
    {
        if (!$this->has($id)) {
            throw new NotFoundException(sprintf('Could not find service with ID %s', $id));
        }

        $invoker = $this->invokers->offsetGet($id);

        return $invoker();
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
                $service = $callable();
            }

            return $service;
        };

        return $this->register($id, $invoker);
    }
}
