<?php

declare(strict_types=1);

namespace Ypf\Application;

use GuzzleHttp\Psr7\ServerRequest;
use Ypf\Application;

class Cgi
{
    public function build()
    {
        return $this;
    }

    public function run(): void
    {
        $request = ServerRequest::fromGlobals();
        $request = $request->withAttribute('rawContent', file_get_contents('php://input'));
        $response = Application::getInstance()->handleRequest($request);

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
