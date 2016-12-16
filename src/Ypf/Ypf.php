<?php
/**
 * Ypf - a micro PHP7 framework
 */

namespace Ypf;

use Ypf\Core\Action;

define("__YPF__", __DIR__);

class Ypf {
	const VERSION = '1.1.0';

	private $container = [];

	protected static $userSettings = [];

	private $before_action = [];
	private $after_action = [];
	private $default_action = null;

	protected static $instances = null;

	public static function autoload($className) {
		$thisClass = str_replace(__NAMESPACE__ . '\\', '', __CLASS__);
		$baseDir = __DIR__;
		if (substr(trim($className, '\\'), 0, strlen($thisClass)) === $thisClass) {
			$baseDir = substr($baseDir, 0, -strlen($thisClass));
		} else {
			$baseDir = self::getUserSetting('root');
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
		//echo $fileName . "\n\n";
		if (file_exists($fileName)) {
			require $fileName;
		}
	}

	public function __construct(array $userSettings = []) {
		self::$userSettings = $userSettings;
		spl_autoload_register(__NAMESPACE__ . "\\Ypf::autoload");

		if (self::getUserSetting('time_zone')) {
			date_default_timezone_set(self::$userSettings['time_zone']);
		}

		if (self::getUserSetting('friendly_error') && PHP_SAPI !== 'cli') {
			self::registerErrorHandle();
		}

		self::$instances = &$this;
	}

	public static function registerErrorHandle() {

		register_shutdown_function(array(new \Ypf\Lib\ErrorHandle(), 'Shutdown'));
		set_error_handler(array(new \Ypf\Lib\ErrorHandle(), 'Error'));
		set_exception_handler(array(new \Ypf\Lib\ErrorHandle(), 'Exception'));

	}

	public static function &getInstance() {
		return self::$instances;
	}

	public static function &getContainer() {
		return self::$instances->container;
	}

	public function addBeforeAction($action, $args = []) {

		$this->before_action[] = self::action($action, $args);
		return $this;
	}

	public function addAfterAction($action, $args = []) {

		$this->after_action[] = self::action($action, $args);
		return $this;
	}

	public function setDefaultAction($action, $args = []) {
		$this->default_action = self::action($action, $args);
	}

	private static function action($action, $args = []) {
		$a = false;
		if (is_object($action) && $action instanceof Action) {
			$a = $action;
		}elseif(is_string($action)){
			$a = new Action($action, $args);
		}else{
			throw new Exception("$action not object or string"); 
		}
		return $a;
	}

	private function execute($action) {
		$result = $action->execute();

		if ($result instanceof Action) {
			return $result;
		} 

	}

	public function start() {
		$this->disPatch();
	}
	
	public function disPatch() {
		$action = $this->default_action;
		foreach ($this->before_action as $before_action) {
			$result = $this->execute($before_action);

			if ($result instanceof Action) {
				$action = $result;
				break;
			}
		}

		while ($action instanceof Action) {
			$action = $this->execute($action);
		}

		foreach ($this->after_action as $after_action) {
			$this->execute($after_action);
		}
	}


	private static function getUserSetting($key) {
		return isset(self::$userSettings[$key]) ? self::$userSettings[$key] : null;
	}

	public function set($name, $value) {
		return $this->__set($name, $value);
	}

	public function get($name) {
		return $this->__get($name);
	}

	public function __get($name) {
		return $this->container[$name];
	}

	public function __set($name, $value) {
		$this->container[$name] = $value;
	}

	public function __isset($name) {
		return isset($this->container[$name]);
	}

	public function __unset($name) {
		unset($this->container[$name]);
	}
}
