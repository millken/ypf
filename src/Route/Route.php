<?php

declare(strict_types=1);

namespace Ypf\Route;

use Psr\Http\Message\ServerRequestInterface;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

class Route
{
    /**
     * @var callable|string
     */
    protected $handler;
    protected $method;
    protected $path;
    protected $host;
    protected $scheme;
    protected $isStaticRoute = false;
    private $headers = [];
    private $parameters = [];

    public function __construct(string $method, string $path, $handler)
    {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->handler = $handler;
    }

    public function setStatic()
    {
        $this->isStaticRoute = true;

        return $this;
    }

    public function isStaticRoute()
    {
        return $this->isStaticRoute;
    }

    public function setHost(string $host)
    {
        $this->host = $host;

        return $this;
    }

    public function getCallable(?ContainerInterface $container = null): callable
    {
        $callable = $this->handler;

        if (is_string($callable) && strpos($callable, '::') !== false) {
            $callable = explode('::', $callable);
        }

        if (is_array($callable) && isset($callable[0]) && is_object($callable[0])) {
            $callable = [$callable[0], $callable[1]];
        }

        if (is_array($callable) && isset($callable[0]) && is_string($callable[0])) {
            $class = (!is_null($container) && $container->has($callable[0]))
                ? $container->get($callable[0])
                : new $callable[0]()
            ;

            $callable = [$class, $callable[1]];
        }

        if (is_string($callable) && method_exists($callable, '__invoke')) {
            $callable = (!is_null($container) && $container->has($callable))
                ? $container->get($callable)
                : new $callable()
            ;
        }

        if (!is_callable($callable)) {
            throw new InvalidArgumentException('Could not resolve a callable for this handle : '.$this->handler);
        }

        return $callable;
    }

    public function setPath(string $path)
    {
        $this->path = $path;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getParameters(): iterable
    {
        return $this->parameters;
    }

    public function getHeaders(): iterable
    {
        return $this->headers;
    }

    public function hasMethod(string $method): bool
    {
        return $this->getMethod() === strtoupper($method);
    }

    public function isMatch(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();
        //match hostname
        if ($this->host) {
            if ($request->getUri()->getHost() !== strtolower($this->host)) {
                return false;
            }
        }
        if ($this->isStaticRoute()) {
            return $this->getPath() == $path;
        } else {
            if (preg_match("~^{$this->getPath()}$~x", $path, $this->parameters)) {
                return true;
            }
        }

        return false;
    }
}
