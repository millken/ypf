<?php

declare(strict_types=1);

namespace Ypf\Controller;

use GuzzleHttp\Psr7\Stream;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class RestController extends Controller
{
    private const HTTP_METHODS = [
        'get',
        'head',
        'post',
        'put',
        'patch',
        'options',
        'connect',
        'delete',
    ];

    private function getEmptyStream(string $mode = 'r'): StreamInterface
    {
        return new Stream(fopen('php://memory', $mode));
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $logger = static::getContainer()->get(\Psr\Log\LoggerInterface::class);
        $logger->debug('Request to {uri}, methood: {method}, class: {class}, rawContent: {rawContent}', [
            'uri' => $request->getUri()->__toString(),
            'method' => $request->getMethod(),
            'class' => get_class($this),
            'rawContent' => $request->getAttribute('rawContent'),
        ]);
        $httpMethod = strtolower($request->getMethod());
        if ($httpMethod === 'head' && !method_exists('head')) {
            $httpMethod = 'get';
        }

        if (!method_exists($this, $httpMethod)) {
            throw new \BadMethodCallException('method not implemented');
        }

        /** @var ResponseInterface $response */
        $response = $this->{$httpMethod}($request, $handler);

        if ($httpMethod === 'head') {
            $response = $response->withBody($this->getEmptyStream());
        }

        if ($httpMethod === 'options') {
            $ref = new \ReflectionObject($this);
            $response = $response->withAddedHeader('allow', array_intersect(
                self::HTTP_METHODS,
                $ref->getMethods(\ReflectionMethod::IS_PUBLIC)
            ));
        }
        $logger->debug('Request to {uri} method: {method} class: {class} rawContent: {rawContent} response: {response}', [
            'uri' => $request->getUri()->__toString(),
            'method' => $request->getMethod(),
            'class' => get_class($this),
            'rawContent' => $request->getAttribute('rawContent'),
            'response' => $response->getBody()->__toString(),
        ]);

        return $response;
    }
}
