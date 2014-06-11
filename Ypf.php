<?php
/**
 * Ypf - a micro PHP 5 framework
 */

namespace Ypf;

if(!defined('__APP__'))  define('__APP__', __DIR__);

class Ypf {
    const VERSION = '0.0.1';

    public $container;


    protected static $apps = array();

    private $pre_action = array();

	protected static $instances = null;

    public static function autoload($className)
    {
        $thisClass = str_replace(__NAMESPACE__.'\\', '', __CLASS__);
        $baseDir = __DIR__;
        if (substr(trim($className, '\\'), 0, strlen($thisClass)) === $thisClass) {
            $baseDir = substr($baseDir, 0, -strlen($thisClass));
        }else{
        	$baseDir = __APP__;
        }
        $baseDir .= '/'; 
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
        }
    }
    
    public function __construct(array $userSettings = array())
    {
		spl_autoload_register(__NAMESPACE__ . "\\Ypf::autoload");
		set_exception_handler(array($this, 'exception_handler'));
    	$this->container = new \Ypf\Helper\Set();
		self::$instances = &$this;
    }

    public static function getInstance()
    {

        return self::$instances;
    }
    
	public function addPreAction($pre_action, $args = array()) {
		$this->pre_action[] = array(
							'action' => $pre_action,
							'args' => $args,
							);
		return $this;
	}

    
	public function execute($action, $args = array()) {
		if (is_array($action)) {
			list($class_name, $method) = $action;
		}else{
			$pos = strrpos($action,'\\');
			$class_name = substr($action, 0, $pos);
			$method = substr($action, $pos + 1);
			
		}
		if(class_exists($class_name) && is_callable(array($class_name, $method))) {
			$class = new $class_name($this->container);
			call_user_func_array(array($class, $method), $args);
			//return $class->getOutput();
		}else{
			throw new Exception("Unable to load action: '$action'[$class_name->{$method}]");
		}
	}
	
	public function disPatch($action='', $args = array()) {
		foreach ($this->pre_action as $pre) {
			$result = $this->execute($pre['action'], $pre['args']);
					
			if ($result) {
				$action = $result;
				
				break;
			}
		}
		while ($action) {
			$action = $this->execute($action);
		}
		
	}
	
	public function exception_handler(Exception $e) {
		printf("<pre style=\"text-align: left; background-color: #fcc; border: 1px solid #600; color: #600; display: block; margin: 1em 0; padding: .33em 6px\">%s(%d): %s (%d) [%s]\n%s\n</pre>",
			   $e->getFile(), $e->getLine(), $e->getMessage(), $e->getCode(), get_class($e), $e->getTraceAsString());
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
