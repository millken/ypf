<?php

declare(strict_types=1);

namespace Ypf\Router;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PrefixRoute extends RegexRoute
{
    protected $prefix;

    public function __construct(string $pattern, string $name = null)
    {
        $this->prefix = $pattern;

        $pattern = rtrim($pattern, '/');
        parent::__construct("{$pattern}/*", $name);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $uri = $request->getUri();
        $uri = $uri->withPath(preg_replace("~^{$this->prefix}/~", '/', $uri->getPath(), 1));

        return parent::handle($request->withUri($uri));
    }
}
