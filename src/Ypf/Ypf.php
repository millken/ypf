<?php
/**
 * Ypf - a micro PHP7 framework
 */

namespace Ypf;

use Ypf\Core\Action;
use Ypf\Reference\ParameterReference;
use Ypf\Reference\ServiceReference;

class Ypf {
    const VERSION = '1.3.0';

    private $container = [];

    protected static $services = [];

    private $pre_action = [];
    private $post_action = [];
    private $default_action = null;

    protected static $instances = null;

    public static function autoload($className) {
        $thisClass = str_replace(__NAMESPACE__ . '\\', '', __CLASS__);
        $baseDir = __DIR__;
        if (substr(trim($className, '\\'), 0, strlen($thisClass)) === $thisClass) {
            $baseDir = substr($baseDir, 0, -strlen($thisClass));
        } else {
            $baseDir = static::$services['root'];
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

    public function __construct(array $services = []) {
        static::$services = $services;

        spl_autoload_register(__NAMESPACE__ . "\\Ypf::autoload");

        if ($this->has('time_zone')) {
            date_default_timezone_set(static::$services['time_zone']);
        }

        if ($this->has('friendly_error') && PHP_SAPI !== 'cli') {
            static::registerErrorHandle();
        }

        if ($this->has('default_action')) {
            $this->default_action = static::action(static::$services['default_action'], []);
        }

        if ($this->has('pre_action')) {
            foreach(static::$services['pre_action'] as $action) {
                if(is_array($action)) {
                    list($action, $args) = $action;
                    $this->pre_action[] = static::action($action, $args);
                }else{
                    $this->pre_action[] = static::action($action, []);
                }
            }
        }
        if ($this->has('post_action')) {
            foreach(static::$services['post_action'] as $action) {
                if(is_array($action)) {
                    list($action, $args) = $action;
                    $this->post_action[] = static::action($action, $args);
                }else{
                    $this->post_action[] = static::action($action, []);
                }
            }
        }
        static::$instances = $this;
    }

    public function has($name) {
        return isset(static::$services[$name]);
    }

    public static function registerErrorHandle() {

        register_shutdown_function(array(new \Ypf\Lib\ErrorHandle(), 'Shutdown'));
        set_error_handler(array(new \Ypf\Lib\ErrorHandle(), 'Error'));
        set_exception_handler(array(new \Ypf\Lib\ErrorHandle(), 'Exception'));

    }

    public static function &getInstance() {
        return static::$instances;
    }

    public static function &getContainer() {
        return static::$instances->container;
    }

    public function addBeforeAction($action, $args = []) {

        $this->pre_action[] = static::action($action, $args);
        return $this;
    }

    public function addAfterAction($action, $args = []) {

        $this->post_action[] = static::action($action, $args);
        return $this;
    }

    public function setDefaultAction($action, $args = []) {
        $this->default_action = static::action($action, $args);
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
        foreach ($this->pre_action as $pre_action) {
            $result = $this->execute($pre_action);

            if ($result instanceof Action) {
                $action = $result;
                break;
            }
        }

        while ($action instanceof Action) {
            $action = $this->execute($action);
        }

        foreach ($this->post_action as $post_action) {
            $this->execute($post_action);
        }
    }

    private function createService($name) {
        $entry = static::$services[$name];
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
        if (!isset(static::$services[$name])) {
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
