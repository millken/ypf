<?php

namespace Ypf\Core;

final class Action 
{
	private $class;
	private $method;
	private $args = array();

	public function __construct($action, $args = array())
	{
		if (is_array($action)) {
			list($class_name, $method) = $action;
		}else{
			$pos = strrpos($action,'\\');
			$class_name = substr($action, 0, $pos);
			$method = substr($action, $pos + 1);
			
		}
		$this->class = $class_name;
		$this->method = $method;
		$this->args = $args;
	}

	public function execute() {
		if (substr($this->method, 0, 2) == '__') {
			return false;
		}
		$class_name = $this->class;
		if(class_exists($class_name) && is_callable(array($class_name, $this->method))) {
			$class = new $class_name();
			return call_user_func_array(array($class, $this->method), $this->args);
		}else{
			return false;
		}
	}

	public function getName() {
		return $this->class;
	}

	public function getMethod() {
		return $this->method;
	}

	public function getArgs() {
		return $this->args;
	}
}
