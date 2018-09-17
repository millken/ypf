<?php

declare(strict_types=1);

namespace Ypf\Application;

use Swoole\Http\Server as SwooleHttpServer;
use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;
use GuzzleHttp\Psr7\ServerRequest;

class Swoole
{
    private $server = null;
    private $app;

    public function build($app)
    {
        $address = '127.0.0.1';
        $port = 7000;
        $this->app = $app;
        $this->server = new SwooleHttpServer($address, $port, SWOOLE_PROCESS, SWOOLE_TCP);

        return $this;
    }

    public function onRequest(SwooleHttpRequest $request, SwooleHttpResponse $swooleResponse): void
    {
        $_SERVER = [];
        foreach ($request->server as $name => $value) {
            $_SERVER[strtoupper($name)] = $value;
        }
        foreach ($request->header as $name => $value) {
            $_SERVER[str_replace('-', '_', strtoupper('HTTP_'.$name))] = $value;
        }
        $_GET = $request->get ?? [];
        $_POST = $request->post ?? [];
        $_COOKIE = $request->cookie ?? [];
        $_FILES = $request->files ?? [];
        $content = $request->rawContent() ?: null;
        $headers = $request->header;
        $request = ServerRequest::fromGlobals();
        foreach ($headers as $header => $line) {
            $request = $request->withHeader($header, $line);
        }
        $request = $request->withAttribute('rawContent', $content);

        $response = $this->app->handleRequest($request);

        $status = $response->getStatusCode();
        $swooleResponse->status($response->getStatusCode());
        foreach ($response->getHeaders() as $header => $values) {
            $swooleResponse->header($header, $response->getHeaderLine($header));
        }
        $swooleResponse->end($response->getBody());
    }

    public function run(): void
    {
        $this->server->on('Request', [$this, 'onRequest']);
        $this->server->start();
    }
}
