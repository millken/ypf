<?php
/**
 * Ypf - a micro PHP7 framework.
 */

namespace Ypf;

use Ypf\Core\Action;
use Ypf\Reference\ParameterReference;
use Ypf\Reference\ServiceReference;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class App implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    const VERSION = '2.0.0';

    private $container = [];

    protected static $services = [];

    protected static $instances = null;

    public function __construct(array $services = [])
    {
        static::$services = $services;

        static::$instances = $this;
    }

    public function has($name)
    {
        return isset(static::$services[$name]);
    }

    public static function &getInstance()
    {
        return static::$instances;
    }

    public static function &getContainer()
    {
        return static::$instances->container;
    }

    private static function action($action, $args = [])
    {
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

    private function execute($action)
    {
        $result = $action->execute();

        if ($result instanceof Action) {
            return $result;
        }
        if ($result instanceof Exception) {
            throw new Exception($result->getMessage());
        }
    }

    public function run()
    {
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

    private function createService($name)
    {
        $entry = static::$services[$name];
        if (!is_array($entry) || !isset($entry['class'])) {
            throw new Exception($name.' service entry must be an array containing a \'class\' key');
        } elseif (!class_exists($entry['class'])) {
            throw new Exception($name.' service class does not exist: '.$entry['class']);
        }
        $arguments = isset($entry['arguments']) ? $this->resolveArguments($entry['arguments']) : [];

        $reflector = new \ReflectionClass($entry['class']);
        $service = $reflector->newInstanceArgs($arguments);
        if (isset($entry['calls'])) {
            $this->initializeService($service, $name, $entry['calls']);
        }

        return $service;
    }

    private function resolveArguments(array $argumentDefinitions)
    {
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

    private function initializeService($service, $name, array $callDefinitions)
    {
        foreach ($callDefinitions as $callDefinition) {
            if (!is_array($callDefinition) || !isset($callDefinition['method'])) {
                throw new Exception($name.' service calls must be arrays containing a \'method\' key');
            } elseif (!is_callable([$service, $callDefinition['method']])) {
                throw new Exception($name.' service asks for call to uncallable method: '.$callDefinition['method']);
            }
            $arguments = isset($callDefinition['arguments']) ? $this->resolveArguments($callDefinition['arguments']) : [];
            call_user_func_array([$service, $callDefinition['method']], $arguments);
        }
    }

    public function set($name, $value)
    {
        return $this->__set($name, $value);
    }

    public function get($name)
    {
        return $this->__get($name);
    }

    public function __get($name)
    {
        if (isset($this->container[$name])) {
            return $this->container[$name];
        }
        if (!isset(static::$services[$name])) {
            throw new Exception('Service not found: '.$name);
        }
        $this->container[$name] = $this->createService($name);

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
