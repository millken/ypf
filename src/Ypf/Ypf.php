<?php
/**
 * Ypf - a micro PHP7 framework
 */

namespace Ypf;

use Ypf\Core\Action;
use Ypf\Reference\ParameterReference;
use Ypf\Reference\ServiceReference;

define("__YPF__", __DIR__);

class Ypf {
	const VERSION = '1.2.0';

	private $container = [];

	protected static $services = [];

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
			$baseDir = self::$services['root'];
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

	public function __construct(array $services = []) {
		self::$services = $services;

		spl_autoload_register(__NAMESPACE__ . "\\Ypf::autoload");

		if ($this->has('time_zone')) {
			date_default_timezone_set(self::$services['time_zone']);
		}

		if ($this->has('friendly_error') && PHP_SAPI !== 'cli') {
			self::registerErrorHandle();
		}

		self::$instances = &$this;
	}

	public function has($name) {
		return isset(self::$services[$name]);
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
		} elseif (is_string($action)) {
			$a = new Action($action, $args);
		} else {
			throw new Exception("$action not object or string");
		}
		return $a;
	}

	private function execute($action) {
		$result = $action->execute();

		if ($result instanceof Action) {
			return $result;
		}
		if ($result instanceof Exception) {
			throw new Exception($result->getMessage());
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

	private function createService($name) {
		$entry = self::$services[$name];
		if (!is_array($entry) || !isset($entry['class'])) {
			throw new Exception($name . ' service entry must be an array containing a \'class\' key');
		} elseif (!class_exists($entry['class'])) {
			throw new Exception($name . ' service class does not exist: ' . $entry['class']);
		}
		$arguments = isset($entry['arguments']) ? $this->resolveArguments($entry['arguments']) : [];

		$reflector = new \ReflectionClass($entry['class']);
		$service = $reflector->newInstanceArgs($arguments);
		if (isset($entry['calls'])) {
			$this->initializeService($service, $name, $entry['calls']);
		}
		return $service;
	}

	private function resolveArguments(array $argumentDefinitions) {
		$arguments = [];
		foreach ($argumentDefinitions as $argumentDefinition) {
			if ($argumentDefinition instanceof ServiceReference) {
				$argumentServiceName = $argumentDefinition->getName();
				$arguments[] = $this->get($argumentServiceName);
			} elseif ($argumentDefinition instanceof ParameterReference) {
				$argumentParameterName = $argumentDefinition->getName();
				$arguments[] = $this->config->get($argumentParameterName);
			} else {
				$arguments[] = $argumentDefinition;
			}
		}
		return $arguments;
	}

	private function initializeService($service, $name, array $callDefinitions) {
		foreach ($callDefinitions as $callDefinition) {
			if (!is_array($callDefinition) || !isset($callDefinition['method'])) {
				throw new Exception($name . ' service calls must be arrays containing a \'method\' key');
			} elseif (!is_callable([$service, $callDefinition['method']])) {
				throw new Exception($name . ' service asks for call to uncallable method: ' . $callDefinition['method']);
			}
			$arguments = isset($callDefinition['arguments']) ? $this->resolveArguments($callDefinition['arguments']) : [];
			call_user_func_array([$service, $callDefinition['method']], $arguments);
		}
	}

	public function set($name, $value) {
		return $this->__set($name, $value);
	}

	public function get($name) {
		return $this->__get($name);
	}

	public function __get($name) {
		if (isset($this->container[$name])) {
			return $this->container[$name];
		}
		if (!isset(self::$services[$name])) {
			throw new Exception('Service not found: ' . $name);
		}
		$this->container[$name] = $this->createService($name);
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
