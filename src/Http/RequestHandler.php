<?php

declare(strict_types=1);

namespace Ypf\Http;

use RuntimeException;
use InvalidArgumentException;
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

            if (!$middleware instanceof MiddlewareInterface) {
                throw new InvalidArgumentException(sprintf('No valid middleware provided (%s)', is_object($middleware) ? get_class($middleware) : gettype($middleware)));
            }
            $this->middleware->next();

            return $middleware->process($request, $this);
        }
        if (null === $this->response) {
            throw new RuntimeException('No base response provided');
        }

        return $this->response;
    }
}
