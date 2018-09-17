<?php

declare(strict_types=1);

namespace Ypf;

use Psr\Http\Message\ServerRequestInterface;
use Ypf\Http\RequestHandler;
use GuzzleHttp\Psr7\Response;
use Ypf\Application\Cgi as DefaultFactory;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Ypf\Log\VoidLogger;

class Application implements LoggerAwareInterface
{
    const VERSION = '3.0.0';

    protected $container;

    protected static $instances = null;
    use LoggerAwareTrait;

    public function __construct(array $services)
    {
        $container = new Container($services);

        $this->container = $container;
        $logger = $container->has('logger') ?
            $container->get('logger') : new VoidLogger();
        $this->setLogger($logger);

        static::$instances = &$this;
    }

    public static function getInstance()
    {
        return static::$instances;
    }

    public static function getContainer()
    {
        return static::$instances->container;
    }

    public function handleRequest(ServerRequestInterface $request)
    {
        try {
            $this->container->add('request', $request);
            $middleware = $this->container->has('middleware') ?
             $this->container->get('middleware') : [];
            $response = $this->container->has('response') ?
            $this->container->get('response') : new Response();
            $requestHandler = new RequestHandler($middleware, $response);

            return $requestHandler->handle($request);
        } catch (\Throwable $ex) {
            $this->logger->critical($ex->getMessage(), [
                'exception' => $ex,
            ]);

            $headers = ['server' => 'YPF-'.static::VERSION];
            $payload = 'HTTP Error 500 Internal Server Error';

            return new Response(500, $headers, $payload);
        }
    }

    public function run()
    {
        $server = $this->container->has('factory') ? $this->container->get('factory') :
        new DefaultFactory();
        $server->build($this)->run();
    }
}
