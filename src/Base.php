<?php

declare(strict_types=1);

namespace Ypf;

abstract class Base
{
    const VERSION = '2.0.0';

    public static function getContainer()
    {
        return Container::getContainer();
    }

    public function __get($name)
    {
        return static::getContainer()->get($name);
    }
}
