<?php

namespace Ypf\Core;

abstract class Model {
    public static $container;

    public function __construct() {
        static::$container = \Ypf\Ypf::getInstance();
    }

    public function __set($name, $value) {
        static::$container->$name = $value;
    }

    public function __get($name) {
        return static::$container->$name;
    }
}
