<?php

namespace Ypf\Core;

abstract class Model {
	public static $container;

	public function __construct() {
		self::$container = \Ypf\Ypf::getContainer();
	}

	public function __set($name, $value) {
		self::$container[$name] = $value;
	}

	public function __get($name) {
		return self::$container[$name];
	}
}
