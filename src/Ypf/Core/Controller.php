<?php

namespace Ypf\Core;

abstract class Controller {
    public static $container;

    public function __construct() {
        static::$container = \Ypf\Ypf::getInstance();
    }

    protected function getCalledControllerName() {
        $qualifiedClassParts = explode('\\', get_called_class());
        return end($qualifiedClassParts);
    }

    protected function action($action, $args = array())    {
        $a = new \Ypf\Core\Action($action, $args);
        return $a->execute();
    }

    public function __set($name, $value) {
        static::$container->$name = $value;
    }

    public function __get($name) {
        return static::$container->$name;
    }
}
