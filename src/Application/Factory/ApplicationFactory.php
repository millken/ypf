<?php

declare(strict_types=1);

namespace Ypf\Application\Factory;

use GuzzleHttp\Psr7\Response;
use Ypf\Application\Application;
use Ypf\Collection\CallbackCollection;
use Ypf\Interfaces\FactoryInterface;
use Ypf\Http\Middleware\RequestHandler;
use Ypf\Log\VoidLogger;
use Ypf\Router\RegexRoute;
use Ypf\Router\Route;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

/**
 * A factory class solely responsible for assembling the Application
 * object that is used as the entry point to all application
 * functionality. It represents the minimal requirements to assemble
 * a fully fledged application be it with or without modules used.
 */
final class ApplicationFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     *
     * @return Application
     */
    public function build(ContainerInterface $container)
    {
        $routeCallback = function ($route) use ($container) {
            $className = RegexRoute::class;
            if (isset($route['class'])) {
                $className = $route['class'];
            }

            $routeObject = new $className($route['pattern'], $route['name'] ?? null);
            if (isset($route['methods'])) {
                $routeObject = $routeObject->withMethods(array_map('strtoupper', $route['methods']));
            }

            if ($routeObject instanceof Route && isset($route['headers'])) {
                $routeObject = $routeObject->withHeaders($route['headers']);
            }

            if (isset($route['request_handler'])) {
                return $routeObject->withRequestHandler($container->get($route['request_handler']));
            }

            $middlewareGenerator = function () use ($route, $container) {
                $stack = array_merge(
                    ($container->has('middleware') ? $container->get('middleware') : []),
                    $route['middleware']
                );
                foreach ($stack as $middleware) {
                    if (is_string($middleware)) {
                        yield $container->get($middleware);
                    } else {
                        assert(
                            is_object($middleware) && $middleware instanceof MiddlewareInterface,
                            new \TypeError("'".get_class($middleware)."' must implement MiddlewareInterface")
                        );
                        yield $middleware;
                    }
                }
            };

            return $routeObject->withRequestHandler(new RequestHandler(
                $middlewareGenerator(),
                $container->has(ResponseInterface::class) ?
                    $container->get(ResponseInterface::class) : new Response()
            ));
        };

        $routes = new CallbackCollection($container->get('routes'), $routeCallback);
        $app = new Application(
            $routes,
            $container->has(RequestHandlerInterface::class) ?
                $container->get(RequestHandlerInterface::class) : null
        );
        $logger = $container->has(\Psr\Log\LoggerInterface::class) ?
            $container->get(\Psr\Log\LoggerInterface::class) : new VoidLogger();

        $app->setLogger($logger);

        return $app;
    }
}
