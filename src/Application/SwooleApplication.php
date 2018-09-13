<?php

declare(strict_types=1);

namespace Ypf\Application;

use Ypf\Interfaces\ApplicationInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareTrait;
use Ypf\Router\Exceptions\NotFoundException;
use GuzzleHttp\Psr7\Response;
use Swoole\Http\Server;
use Ypf\Swoole\StaticResourceHandler;

class SwooleApplication implements ApplicationInterface, LoggerAwareInterface
{
    /** @var \Swoole\Http\Server */
    private $server;
    /**
     * @var RouteInterface[]
     */
    protected $routes = [];

    private $staticResourceHandler;
    private $requestHandler;

    use LoggerAwareTrait;

    public function __construct(array $staticFiles, iterable $routes, RequestHandlerInterface $rootHandler = null, Server $server)
    {
        //check static files
        if ($staticFiles) {
            $this->staticResourceHandler = new StaticResourceHandler($staticFiles);
        }
        $this->routes = $routes;
        $this->requestHandler = $rootHandler;
        $this->server = $server;
    }

    public function run(): void
    {
        $this->server->on('Request', [$this, 'onRequest']);
        $this->server->start();
    }

    public function onRequest(\Swoole\Http\Request $request, \Swoole\Http\Response $response): void
    {
        if ($this->staticResourceHandler) {
            $result = $this->staticResourceHandler->handle($request, $response);
            if ($result) {
                return;
            }
        }
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

        $result = $this->handle($request);
        $status = $result->getStatusCode();
        $reasonPhrase = $result->getReasonPhrase();
        $response->status($result->getStatusCode());
        foreach ($result->getHeaders() as $header => $values) {
            $response->header($header, $result->getHeaderLine($header));
        }
        $response->end($result->getBody());
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $path = $request->getUri()->getPath();
            reset($this->routes);
            foreach ($this->routes as $route) {
                if ($route->isMatch($path)) {
                    foreach ($route->getParameters() as $attr => $value) {
                        $request = $request->withAttribute($attr, $value);
                    }

                    return $route->handle($request);
                }
            }
            throw new NotFoundException(
                "No route available to handle '{$request->getUri()->getPath()}'"
            );
        } catch (MissingHeaderException $ex) {
            $headers = [];
            switch (strtolower($ex->getMessage())) {
                case 'authorization':
                    $status = 401;
                    $headers['WWW-Authenticate'] =
                        "{$this->baseAuthorization} realm=\"{$request->getUri()->getHost()}\" charset=\"UTF-8\"";
                    break;
                case 'proxy-authorization':
                    $status = 407;
                    $headers['Proxy-Authenticate'] =
                        "{$this->proxyAuthorization} realm=\"{$request->getUri()->getHost()}\" charset=\"UTF-8\"";
                    break;
                case 'if-match':
                case 'if-none-match':
                case 'if-modified-since':
                case 'if-unmodified-since':
                case 'if-range':
                    $status = 428;
                    break;
                default:
                    $status = 400;
                    break;
            }
            $this->logger->debug("Request to {url} does not include required header '{header}'. ", [
                'url' => $request->getUri()->getPath(),
                'method' => $ex->getMessage(),
            ]);

            return new Response($status, $headers);
        } catch (NotFoundException $ex) {
            return new Response(404);
        } catch (MethodNotAllowedException $ex) {
            $this->logger->debug("Request to {url} does not support '{method}'. Supported: {allowed}", [
                'url' => $request->getUri()->getPath(),
                'method' => $request->getMethod(),
                'allowed' => implode(', ', $ex->getAllowedMethods()),
            ]);

            return (new Response(405))
                ->withHeader('Allow', $ex->getAllowedMethods());
        } catch (\BadMethodCallException $ex) {
            $this->logger->warning($ex->getMessage(), [
                'exception' => $ex,
            ]);

            return new Response(
                in_array(strtolower($request->getMethod()), ['get', 'head']) ? 503 : 501
            );
        } catch (\Throwable $ex) {
            $this->logger->critical($ex->getMessage(), [
                'exception' => $ex,
            ]);

            return new Response(500);
        } catch (\Exception $ex) {
            $this->logger->critical($ex->getMessage(), [
                'exception' => $ex,
            ]);
        }
    }
}
