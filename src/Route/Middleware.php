<?php

declare(strict_types=1);

namespace Ypf\Route;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Middleware implements MiddlewareInterface
{
    private $router;
    private $attribute = 'request-handler';

    public function __construct($router)
    {
        $this->router = $router;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $route = $this->router->dispatch($request);
        foreach ($route->getParameters() as $attr => $value) {
            $request = $request->withAttribute($attr, $value);
        }
        $request->withAttribute($this->attribute, $route->getCallable());

        return $handler->handle($request);
    }
}
