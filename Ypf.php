<?php
/**
 * Ypf - a micro PHP 5 framework
 */
namespace Ypf;

class Ypf {
    const VERSION = '0.0.1';

    public $container;


    protected static $apps = array();

    protected $name;


    public static function autoload($className)
    {
        $thisClass = str_replace(__NAMESPACE__.'\\', '', __CLASS__);

        $baseDir = __DIR__;

        if (substr($baseDir, -strlen($thisClass)) === $thisClass) {
            $baseDir = substr($baseDir, 0, -strlen($thisClass));
        }

        $className = ltrim($className, '\\');
        $fileName = $baseDir;
        $namespace = '';
        if ($lastNsPos = strripos($className, '\\')) {
            $namespace = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName .= str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        if (file_exists($fileName)) {
            require $fileName;
        }else{
        	echo $fileName . "不存在";
        }
    }


    
    public function __construct(array $userSettings = array())
    {
		spl_autoload_register(__NAMESPACE__ . "\\Ypf::autoload");
    	$this->container = new \Ypf\Helper\Set();
    }
    
	public function set($name, $value) {
		return $this->__set($name, $value);
	}

	public function get($name) {
		return $this->__get($name);
	}
	
    public function __get($name)
    {
        return $this->container[$name];
    }

    public function __set($name, $value)
    {
        $this->container[$name] = $value;
    }

    public function __isset($name)
    {
        return isset($this->container[$name]);
    }

    public function __unset($name)
    {
        unset($this->container[$name]);
    }    
}
