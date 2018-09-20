<?php

declare(strict_types=1);

namespace Ypf\Route;

use GuzzleHttp\Psr7\Response;
use Ypf\Route\Exception\NotFoundException;
use Ypf\Route\Exception\MethodNotAllowedException;
use Ypf\Route\Exception\MissingHeaderException;
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
        try {
            $route = $this->router->dispatch($request);
            foreach ($route->getHeaders() as $header => $required) {
                if ((bool) $required && !$request->hasHeader($header)) {
                    throw new MissingHeaderException($header);
                }
            }
            if (!$route->hasMethod($request->getMethod())) {
                throw new MethodNotAllowedException($route->getMethod());
            }

            foreach ($route->getParameters() as $attr => $value) {
                $request = $request->withAttribute($attr, $value);
            }
            $request = $request->withAttribute($this->attribute, $route->getCallable());

            return $handler->handle($request);
        } catch (NotFoundException $ex) {
            return new Response(404);
        } catch (MethodNotAllowedException $ex) {
            return new Response(405);
        } catch (MissingHeaderException $ex) {
            return new Response(400);
        }
    }
}
