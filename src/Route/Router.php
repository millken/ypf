<?php

declare(strict_types=1);

namespace Ypf\Route;

use Psr\Http\Message\ServerRequestInterface;
use Ypf\Route\Exception\NotFoundException;

class Router
{
    protected $routes = [];
    protected $staticRoutes = [];
    protected $regexpRoutes = [];

    public function __construct()
    {
    }

    private function parse(string $path): string
    {
        return preg_replace(
            ['~\{(\w+)\}+~iuU', '~\{(\w+)\:(.*)\}+~iuU', '~\{/?(.*)\}\?~iuU', '~\{(.*)\}\?~iuU'],
            ['(?P<$1>[^/]+)', '(?P<$1>$2)', '(?:$1)?', '(?:$1)?'],
            str_replace('/*', '/(?:.*)', $path)
        );
    }

    public function map(string $method, string $path, $handler): Route
    {
        $path = sprintf('/%s', ltrim($path, '/'));

        $route = new Route($method, $path, $handler);
        if (preg_match("~\{(\w+)\}+~iuU", $path)) {
            $this->regexpRoutes[] = $route->setPath($this->parse($path));
        } else {
            $this->staticRoutes[] = $route->setStatic();
        }

        return $route;
    }

    public function get(string $path, $handler): Route
    {
        return $this->map('GET', $path, $handler);
    }

    public function post(string $path, $handler): Route
    {
        return $this->map('POST', $path, $handler);
    }

    public function put(string $path, $handler): Route
    {
        return $this->map('PUT', $path, $handler);
    }

    public function delete(string $path, $handler): Route
    {
        return $this->map('DELETE', $path, $handler);
    }

    public function dispatch(ServerRequestInterface $request): Route
    {
        $routes = array_merge($this->staticRoutes, $this->regexpRoutes);
        foreach ($routes as $route) {
            if ($route->isMatch($request)) {
                return $route;
            }
        }

        throw new NotFoundException(
            "No route available to handle '{$request->getUri()->getPath()}'"
        );
    }
}
