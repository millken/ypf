<?php

declare(strict_types=1);

namespace Ypf;

use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Ypf\Http\RequestHandler;
use GuzzleHttp\Psr7\Response;

class Application
{
    const VERSION = '3.0.0';

    public static $container;
    private static $requestHandler;

    public function __construct(array $services)
    {
        $container = new Container($services);

        static::$requestHandler = new RequestHandler($container->get('middleware'), $container->has('response') ?
        $container->get('response') : new Response());
        static::$container = $container;
    }

    public static function handleRequest(ServerRequestInterface $request)
    {
        try {
            static::$container->add('request', $request);

            // $dispatcher = new \Middleland\Dispatcher(static::$container->get('middleware'));

            // return $dispatcher->dispatch($request);
            $dispatcher = new \Moon\HttpMiddleware\Delegate(static::$container->get('middleware'), function () {}, static::$container);

            return $dispatcher->handle($request);
            //return static::$requestHandler->handle($request);
        } catch (Exception $exception) {
            throw $exception;
        }
    }

    public static function getContainer()
    {
        return static::$container;
    }

    public function run()
    {
        $server = static::$container->has('factory') ? static::$container->get('factory') : null;
        $server->build(static::$container)->run();
    }
}
