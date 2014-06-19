<?php

namespace Ypf\Lib;

class Load {

	private static $base = '';

    public function __construct($base = '')
    {
    	self::$base = $base;
    }

	public function library($library, $params = NULL, $object_name = NULL) {
		if (empty($library))
		{
			return $this;
		}
		elseif (is_array($library))
		{
			foreach ($library as $key => $value)
			{
				if (is_int($key))
				{
					$this->library($value, $params);
				}
				else
				{
					$this->library($key, $params, $value);
				}
			}

			return $this;
		}


		$this->load_class($library, $params, $object_name);
		return $this;
	}

	public function helper() {
		$args = func_get_args();

		if(func_num_args() == 1 && is_array($args[0])) {
			$helpers = $args[0];
		}else{
			$helpers = $args;
		}
		foreach ($helpers as $helper) {
			$helper = str_replace('.php', '', trim($helper, '/'));
			$file = self::$base .'/Helper/'.$helper.'.php';
			if(!is_file($file))trigger_error('Unable to load the helper file: '.$file);
			include_once($file);
		}
	}

	protected function load_class($class, $params = NULL, $object_name = NULL)
	{
		// Get the class name, and while we're at it trim any slashes.
		// The directory path can be included as part of the class name,
		// but we don't want a leading slash
		$class = str_replace('.php', '', trim($class, '/'));

		// Was the path included with the class name?
		// We look for a slash to determine this
		if (($last_slash = strrpos($class, '/')) !== FALSE)
		{
			// Extract the path
			$subdir = substr($class, 0, ++$last_slash);

			// Get the filename from the path
			$class = substr($class, $last_slash);
		}
		else
		{
			$subdir = '';
		}

		$class = ucfirst($class);
		$subclass = self::$base .'/Library/'.$subdir.$class.'.php';
		include_once($subclass);

		if (empty($object_name))
		{
			$object_name = strtolower($class);
		}

		// Don't overwrite existing properties
		$ypf =\Ypf\Ypf::getInstance();
		if (isset($ypf->$object_name))
		{
			trigger_error("Resource '".$object_name."' already exists and is not a ".$class." instance.");
		}

		// Instantiate the class
		$ypf->$object_name = isset($params)
			? new $class($params)
			: new $class();
	}
}
