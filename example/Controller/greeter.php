<?php

declare(strict_types=1);

namespace Controller;

use Psr\Http\Message\ResponseInterface;
use Ypf\Controller\RestController;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use GuzzleHttp\Psr7\Response;

class Greeter extends RestController
{
    public function get(ServerRequestInterface $request, RequestHandlerInterface $requestHandler): ResponseInterface
    {
        $name = ucwords($request->getAttribute('name', 'World!'));

        $result = [
            'name' => $name,
        ];

        $headers['content-type'] = 'application/json';
        $payload = json_encode($result);

        return new Response(200, $headers, $payload);
    }

    public function put(ServerRequestInterface $request, RequestHandlerInterface $requestHandler)
    {
    }
}
