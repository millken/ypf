<?php

declare(strict_types=1);

namespace Ypf\Http;

use Psr\Http\Message;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Ypf\Application;

final class RequestHandler implements RequestHandlerInterface
{
    private $middleware;

    private $response;

    public function __construct(iterable $middleware, Message\ResponseInterface $response = null)
    {
        if (is_array($middleware)) {
            $middleware = new \ArrayIterator($middleware);
        }

        $this->middleware = $middleware;
        $this->response = $response;
    }

    public function handle(Message\ServerRequestInterface $request): Message\ResponseInterface
    {
        if ($this->middleware->valid()) {
            $middleware = Application::getContainer()->get($this->middleware->current());

            assert($middleware instanceof MiddlewareInterface, new \TypeError('Invalid middleware type'));
            $this->middleware->next();

            return $middleware->process($request, $this);
        }

        $this->middleware->rewind();

        if (null === $this->response) {
            throw new \RuntimeException('No base response provided');
        }

        return $this->response;
    }
}
