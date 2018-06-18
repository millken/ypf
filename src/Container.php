<?php

declare(strict_types=1);

namespace Ypf;

use Psr\Container\ContainerInterface;
use Ypf\Exceptions\ContainerException;
use Ypf\Exceptions\ContainerValueNotFoundException;
use Ypf\Interfaces\FactoryInterface;

/**
 * Class Container.
 */
class Container implements ContainerInterface
{
    private $dependencies = [];
    /** @var ContainerInterface */
    private static $services = [];

    /**
     * Container constructor.
     *
     * @param object $dependencies
     */
    public function __construct(array $services)
    {
        static::$services = $services;

        $this->dependencies = new \stdClass();
    }

    public function get($key)
    {
        $key = (string) $key;

        if (isset($this->dependencies->$key)) {
            return $this->dependencies->$key;
        }

        try {
            if ($key == FactoryInterface::class) {
                $name = static::$services[$key];
                assert(
                    is_string($name),
                    new ContainerException(
                        "Registered factory for '{$key}' must be a valid FQCN, ".gettype($key).' given'
                    )
                );

                $factory = (new \ReflectionClass($name))
                    ->newInstance();

                assert(
                    $factory instanceof FactoryInterface,
                    new ContainerException(
                        "Factory for '{$key}' does not implement Interfaces\\FactoryInterface"
                    )
                );

                return $factory->build($this);
            }

            if (class_exists($key)) {
                $this->dependencies->{$key} = $this->retrieveFromReflection($key);

                return $this->dependencies->{$key};
            }

            if (isset(static::$services[$key])) {
                if (is_string(static::$services[$key]) and class_exists(static::$services[$key])) {
                    $this->dependencies->{$key} = $this->retrieveFromReflection(static::$services[$key]);

                    return $this->dependencies->{$key};
                }

                if (is_callable(static::$services[$key])) {
                    $this->dependencies->{$key} = call_user_func(static::$services[$key]);

                    return $this->dependencies->{$key};
                } else {
                    return static::$services[$key];
                }
            }
        } catch (\RuntimeException $ex) {
            throw new ContainerException($ex->getMessage(), 0, $ex);
        }

        throw new ContainerValueNotFoundException(sprintf('Unable to resolve "%s"', $key));
    }

    /**
     * @param string $className
     *
     * @return mixed|object
     *
     * @throws ContainerErrorException
     */
    private function retrieveFromReflection(string $className)
    {
        $classReflection = new \ReflectionClass($className);
        if ($classReflection->getConstructor() === null) {
            return $classReflection->newInstanceWithoutConstructor();
        }

        return $classReflection->newInstance();
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * @param string $key identifier of the entry to look for
     *
     * @return bool
     */
    public function has($key): bool
    {
        $key = (string) $key;
        $exists = (
            isset($this->dependencies->$key) ||
            class_exists($key) ||
            isset(static::$services[$key])
        );

        return $exists;
    }
}
