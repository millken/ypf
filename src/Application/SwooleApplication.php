<?php

declare(strict_types=1);

namespace Ypf\Application;

use GuzzleHttp\Psr7\Response;
use Ypf\Interfaces\ApplicationInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Http\Message\ServerRequestInterface;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareTrait;
use Ypf\Router\Exceptions\NotFoundException;

class SwooleApplication implements ApplicationInterface, LoggerAwareInterface
{
    /**
     * @var RouteInterface[]
     */
    protected $routes = [];

    private $requestHandler;

    use LoggerAwareTrait;

    public function __construct(iterable $routes, RequestHandlerInterface $rootHandler = null)
    {
        $this->routes = $routes;
        $this->requestHandler = $rootHandler;
    }

    public function run(): void
    {
        $request = ServerRequest::fromGlobals();
        $response = $this->handle($request);
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
        }
    }
}
