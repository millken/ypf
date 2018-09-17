<?php

declare(strict_types=1);

namespace Ypf\Application;

use GuzzleHttp\Psr7\ServerRequest;

class Cgi
{
    private $app;

    public function build($app)
    {
        $this->app = $app;

        return $this;
    }

    public function run(): void
    {
        $request = ServerRequest::fromGlobals();
        $request = $request->withAttribute('rawContent', file_get_contents('php://input'));
        $response = $this->app->handleRequest($request);
        $status = $response->getStatusCode();
        $reasonPhrase = $response->getReasonPhrase();
        header(
            "HTTP/{$response->getProtocolVersion()} {$status} {$reasonPhrase}",
            true,
            $status
        );
        foreach ($response->getHeaders() as $header => $values) {
            foreach ($values as $index => $value) {
                header("{$header}: {$value}", $index === 0);
            }
        }
        file_put_contents('php://output', $response->getBody());
    }
}
