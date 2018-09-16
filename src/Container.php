<?php

declare(strict_types=1);

namespace Ypf;

use Psr\Container\ContainerInterface;

class Container implements ContainerInterface
{
    private $dependencies = [];

    private static $services = [];

    protected static $instances = null;

    public function __construct(array $services)
    {
        static::$services = $services;

        $this->dependencies = new \stdClass();
        static::$instances = $this;
    }

    public static function &getContainer()
    {
        return static::$instances;
    }

    public function add(string $id, $concrete = null)
    {
        static::$services[$id] = $concrete;
    }

    public function get($key)
    {
        if (is_object($key)) {
            return $key;
        }
        $key = (string) $key;

        if (isset($this->dependencies->$key)) {
            return $this->dependencies->$key;
        }

        try {
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
            throw new \Exception($ex->getMessage(), 0, $ex);
        }

        throw new \Exception(sprintf('Unable to resolve "%s"', $key));
    }

    private function retrieveFromReflection(string $className)
    {
        $classReflection = new \ReflectionClass($className);
        if ($classReflection->getConstructor() === null) {
            return $classReflection->newInstanceWithoutConstructor();
        }

        return $classReflection->newInstance();
    }

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
