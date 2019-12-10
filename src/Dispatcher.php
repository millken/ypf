<?php

declare(strict_types=1);

namespace Ypf;

use GuzzleHttp\Psr7\Response;
use ArrayIterator;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Dispatcher implements RequestHandlerInterface
{
    private $middleware;
    private $handlerAttribute = 'request-handler';

    public function __construct(iterable $middleware)
    {
        if (is_array($middleware)) {
            $middleware = new ArrayIterator($middleware);
        }

        $this->middleware = $middleware;
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handle($request)->withHeader('X-Powered-By', 'YPF/'.Application::VERSION);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->middleware->valid()) {
            $middleware = Application::getContainer()->get($this->middleware->current());
            if (!$middleware instanceof MiddlewareInterface) {
                throw new InvalidArgumentException(sprintf('No valid middleware provided (%s)', is_object($middleware) ? get_class($middleware) : gettype($middleware)));
            }
            $this->middleware->next();

            return $middleware->process($request, $this);
        }

        $requestHandler = $request->getAttribute($this->handlerAttribute);
        $response = call_user_func($requestHandler, $request);

        if (is_string($response)) {
            $response = new Response(200, [], $response);
        }

        return $response;
    }
}
