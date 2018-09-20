<?php

declare(strict_types=1);

namespace Ypf\Session;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Middleware implements MiddlewareInterface
{
    /**
     * The user defined session name.
     *
     * @var string|null
     */
    private $name = 'ypf_session';
    /**
     * The user defined session save path.
     *
     * @var string|null
     */
    private $save_path;
    /**
     * The user defined session cache limiter.
     *
     * @var string|null
     */
    private $cache_limiter;
    /**
     * The user defined session cache expire.
     *
     * @var int|null
     */
    private $cache_expire;
    /**
     * The user defined session cookie params.
     *
     * @var array
     */
    private $cookie_params;

    public function __construct(
        string $name = null,
        string $save_path = null,
        string $cache_limiter = null,
        int $cache_expire = null,
        array $cookie_params = []
    ) {
        $this->name = $name;
        $this->save_path = $save_path;
        $this->cache_limiter = $cache_limiter;
        $this->cache_expire = $cache_expire;
        $this->cookie_params = $cookie_params;
    }

    public function withName(string $name): Middleware
    {
        return new Middleware(
            $name,
            $this->save_path,
            $this->cache_limiter,
            $this->cache_expire,
            $this->cookie_params
        );
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $id = $request->getCookieParams()[$this->name] ?? '';
    }
}
