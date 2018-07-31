<?php

declare(strict_types=1);

namespace Ypf\Controller;

use Psr\Http\Server\MiddlewareInterface;

abstract class Controller implements MiddlewareInterface
{
    public static $container;

    public function __construct()
    {
        static::$container = \Ypf\Container::getContainer();
    }

    public function __get($name)
    {
        return static::$container->get($name);
    }
}
