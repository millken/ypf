<?php

declare(strict_types=1);

namespace Ypf\Router;

use Ypf\Router\Exceptions\MethodNotAllowedException;
use Ypf\Router\Exceptions\MissingHeaderException;
use Ypf\Router\Interfaces\RouteInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class Route implements RouteInterface
{
    private $name;
    private $pattern;
    private $handler;
    private $methods = [];
    private $headers = [];

    private $parameters = [];

    public function __construct(string $pattern, string $name = null)
    {
        $this->pattern = $pattern;
        $this->name = $name ?? $pattern;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMethods(): iterable
    {
        return $this->methods;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function getRequestHandler(): RequestHandlerInterface
    {
        return $this->handler;
    }

    public function getParameters(): iterable
    {
        return $this->parameters;
    }

    public function getHeaders(): iterable
    {
        return $this->headers;
    }

    public function hasName(): bool
    {
        return $this->name !== null;
    }

    public function hasMethod(string $method): bool
    {
        return $this->methods === [] || in_array(strtolower($method), $this->methods);
    }

    public function withMethods(iterable $methods): RouteInterface
    {
        if ($methods instanceof \Iterator) {
            $methods = iterator_to_array($methods, false);
        }
        $self = clone $this;
        $self->methods = array_map('strtolower', $methods);

        return $self;
    }

    public function withRequestHandler(RequestHandlerInterface $requestHandler): RouteInterface
    {
        $self = clone $this;
        $self->handler = $requestHandler;

        return $self;
    }

    public function withHeaders(iterable $headers): RouteInterface
    {
        if ($headers instanceof \Iterator) {
            $headers = iterator_to_array($headers, true);
        }

        $self = clone $this;
        $self->headers = $headers;

        return $self;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        foreach ($this->getHeaders() as $header => $required) {
            if ((bool) $required && !$request->hasHeader($header)) {
                throw new MissingHeaderException($header);
            }
        }

        if (!$this->hasMethod($request->getMethod())) {
            throw new MethodNotAllowedException($this->getMethods());
        }

        $response = $this->getRequestHandler()->handle($request);

        return $response;
    }
}
