<?php

namespace Ypf\Core;

abstract class Controller
{
	public static $container;
		
	public function __construct($container)
	{
		self::$container = $container;
	}
	
	public function forward($action, $args = array())
	{
		\Ypf\Ypf::getInstance()->execute($action, $args);
	}
	
	public function __set($name, $value) {
		self::$container[$name] = $value;
	}

    public function __get($name)
    {
        return self::$container[$name];
    }
}
