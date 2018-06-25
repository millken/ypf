<?php

declare(strict_types=1);

namespace Ypf\Router\Interfaces;

use Psr\Http\Server\RequestHandlerInterface;

interface RouteInterface extends RequestHandlerInterface
{
    /** Get the readable name of the route */
    public function getName(): string;

    /** Get a list of the supported methods */
    public function getMethods(): iterable;

    /** Get the pattern of the route */
    public function getPattern(): string;

    public function getRequestHandler(): RequestHandlerInterface;

    public function getParameters(): iterable;

    public function getHeaders(): iterable;

    /** Check if the route has a name */
    public function hasName(): bool;

    /** Check if a route supports a specific method */
    public function hasMethod(string $method): bool;

    /** Add methods that are supported by the route */
    public function withMethods(iterable $methods): self;

    /** Attach the request handler for the route */
    public function withRequestHandler(RequestHandlerInterface $requestHandler): self;

    /** Add headers to the after execution */
    public function withHeaders(iterable $headers): self;

    /** Attempt to match the route against $path */
    public function isMatch(string $path): bool;
}
