<?php

declare (strict_types = 1);

namespace Ypf\Http;

use function GuzzleHttp\Psr7\stream_for;
use Psr\Http\Message\ResponseInterface as Psr7ResponseInterface;

class Response
{

    protected $response;
    public function __construct(?Psr7ResponseInterface $response = null)
    {
        $this->response = $response;
    }

    public function json($data): Psr7ResponseInterface
    {
        $data = json_encode($data);
        $data = stream_for($data);
        return $this->response
            ->withAddedHeader('content-type', 'application/json; charset=utf-8')
            ->withBody($data);
    }

    public function redirect($url): Psr7ResponseInterface
    {
        return $this->response
            ->withAddedHeader('Location', $url)
            ->withStatus(302);
    }
}
