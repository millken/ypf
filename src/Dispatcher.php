<?php

declare(strict_types=1);

namespace Ypf;

use ArrayIterator;
use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class Dispatcher implements RequestHandlerInterface
{
    private $middleware;

    public function __construct(iterable $middleware)
    {
        if (is_array($middleware)) {
            $middleware = new ArrayIterator($middleware);
        }

        $this->middleware = $middleware;
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handle($request);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        var_dump($this->middleware->current());
        $middleware = Application::getContainer()->get($this->middleware->current());

        if (!$middleware instanceof MiddlewareInterface) {
            throw new InvalidArgumentException(sprintf('No valid middleware provided (%s)', is_object($middleware) ? get_class($middleware) : gettype($middleware)));
        }
        $this->middleware->next();

        return $middleware->process($request, $this);
    }
}
