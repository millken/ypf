<?php

declare(strict_types=1);

namespace Ypf\Application\Factory;

use GuzzleHttp\Psr7\Response;
use Ypf\Application\SwooleApplication;
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

/**
 * A factory class solely responsible for assembling the Application
 * object that is used as the entry point to all application
 * functionality. It represents the minimal requirements to assemble
 * a fully fledged application be it with or without modules used.
 */
final class SwooleApplicationFactory implements FactoryInterface
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
                    yield $container->get($middleware);
                }
            };

            return $routeObject->withRequestHandler(new RequestHandler(
                $middlewareGenerator(),
                $container->has(ResponseInterface::class) ?
                    $container->get(ResponseInterface::class) : new Response()
            ));
        };

        $swoole = $container->get('swoole');
        $listen = isset($swoole['listen']) ? $swoole['listen'] : '127.0.0.1:';

        list($address, $port) = explode(':', $listen, 2);

        $port = !empty($port) ? (int) $port : $this->getRandomPort($address);
        $server = new \Swoole\Http\Server($address, $port, SWOOLE_PROCESS, SWOOLE_TCP);

        if (isset($swoole['ssl_listen'])) {
            list($ssl_addr, $ssl_port) = explode(':', $swoole['ssl_listen'], 2);
            $server->addlistener($ssl_addr, $ssl_port, SWOOLE_TCP | SWOOLE_SSL);
        }
        isset($swoole['options']) && $server->set($swoole['options']);

        if (isset($swoole['user'])) {
            $user = posix_getpwnam($swoole['user']);
            if ($user) {
                posix_setuid($user['uid']);
                posix_setgid($user['gid']);
            }
        }

        $routes = new CallbackCollection($container->get('routes'), $routeCallback);
        $app = new SwooleApplication(
            $routes,
            $container->has(RequestHandlerInterface::class) ?
                $container->get(RequestHandlerInterface::class) : null,
                $server
        );
        $logger = $container->has(\Psr\Log\LoggerInterface::class) ?
            $container->get(\Psr\Log\LoggerInterface::class) : new VoidLogger();
        $logger->warning("Swoole HTTP Server listen: $address:$port");
        $app->setLogger($logger);

        return $app;
    }

    private function getRandomPort($address): int
    {
        while (true) {
            $port = mt_rand(1025, 65000);
            $fp = @fsockopen($address, $port, $errno, $errstr, 0.1);
            if (!$fp) {
                break;
            }
        }

        return $port;
    }
}
