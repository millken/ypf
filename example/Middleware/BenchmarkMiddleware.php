<?php

declare(strict_types=1);

namespace Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class BenchmarkMiddleware implements MiddlewareInterface
{
    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $requestHandler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $requestHandler): ResponseInterface
    {
        $start = memory_get_usage();
        $response = $requestHandler->handle($request);
        $end = memory_get_usage();
        $stream = $response->getBody();
        $stream->seek($stream->getSize() ?? 0);
        $stream->write(' -- Memory usage '.number_format(($end - $start) / 1024, 3).'KB');

        return $response;
    }
}
