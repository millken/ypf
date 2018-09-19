<?php

declare(strict_types=1);

namespace Ypf\Route;

use Ypf\Route\Exception\NotFoundException;
use Ypf\Route\Exception\MethodNotAllowedException;
use Ypf\Route\Exception\MissingHeaderException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use GuzzleHttp\Psr7\Response;

class Middleware implements MiddlewareInterface
{
    private $router;

    public function __construct($router)
    {
        $this->router = $router;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $response = $this->router->dispatch($request);
        } catch (NotFoundException $ex) {
            return new Response(404);
        } catch (MethodNotAllowedException $ex) {
            return new Response(405);
        } catch (MissingHeaderException $ex) {
            return new Response(400);
        } catch (\Throwable $ex) {
            throw $ex;

            return new Response(500);
        }

        return $response;
    }
}
