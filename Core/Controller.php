<?php

namespace Ypf\Core;

abstract class Controller
{
	public static $container;
	
	public function __construct($container)
	{
		self::$container = $container;
	}
}
