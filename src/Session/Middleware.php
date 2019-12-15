<?php

declare(strict_types=1);

namespace Ypf\Session;

use Yac;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Middleware implements MiddlewareInterface
{
    const DATE_FORMAT = 'D, d-M-Y H:i:s T';
    /**
     * The date for the expired value of cache limiter header.
     *
     * @var string
     */
    const EXPIRED = 'Thu, 19 Nov 1981 08:52:00 GMT';
    /**
     * The user defined session name.
     *
     * @var string|null
     */
    private $name = 'ypf_session';
    private $id;

    private $cache;

    private $cache_limiter = 'nocache';

    private $cache_expire = 180;

    private $cookie_params = [
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => false,
    ];

    public function __construct()
    {
        $this->cache = new Yac('session_');

        return $this;
    }

    public function withName(string $name): Middleware
    {
        $this->name = $name;

        return $this;
    }

    public function withCacheLimiter(string $cache_limiter): Middleware
    {
        $this->cache_limiter = $cache_limiter;

        return $this;
    }

    public function withCacheExpire(int $cache_expire): Middleware
    {
        $this->cache_expire = $cache_expire;

        return $this;
    }

    public function withCookieParams(array $cookie_params): Middleware
    {
        $this->cookie_params = $cookie_params;

        return $this;
    }

    protected function generateId(): string
    {
        return md5(random_bytes(16));
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->id = $request->getCookieParams()[$this->name] ?? null;

        $_SESSION = [];
        if ($this->id && strlen($this->id) == 32 && ctype_xdigit($this->id)) {
            $value = $this->cache->get(substr($this->id, 0, 32));
            if (!$value) {
                $_SESSION = [];
            } else {
                $_SESSION = $value;
            }
        } else {
            $this->id = null;
        }
        $session = $_SESSION;

        $request = $request->withAttribute('session', new Session($_SESSION));
        $response = $handler->handle($request);

        if ($session !== $_SESSION && $this->id) {
            $this->cache->set(substr($this->id, 0, 32), $_SESSION, $this->cache_expire * 60);
        }
        if (!$this->id) {
            $this->id = $this->generateId();
            $response = $this->attachSessionHeaders($response);
        }

        return $response;
    }

    private function attachSessionHeaders(ResponseInterface $response): ResponseInterface
    {
        $time = time();
        $response = $this->attachCacheLimiterHeader($response, $time);
        $response = $this->attachSessionCookie($response, $time);

        return $response;
    }

    private function attachNocacheCacheLimiterHeader(ResponseInterface $response): ResponseInterface
    {
        return $response
            ->withAddedHeader('Expires', static::EXPIRED)
            ->withAddedHeader('Cache-Control', 'no-store, no-cache, must-revalidate')
            ->withAddedHeader('Pragma', 'no-cache');
    }

    private function attachCacheLimiterHeader(ResponseInterface $response, int $time): ResponseInterface
    {
        switch ($this->cache_limiter) {
            case 'public':
                return $this->attachPublicCacheLimiterHeader($response, $time);
            case 'private':
                return $this->attachPrivateCacheLimiterHeader($response, $time);
            case 'private_no_expire':
                return $this->attachPrivateNoExpireCacheLimiterHeader($response, $time);
            case 'nocache':
                return $this->attachNocacheCacheLimiterHeader($response);
            default:
                return $response;
        }
    }

    private function attachPublicCacheLimiterHeader(ResponseInterface $response, int $time): ResponseInterface
    {
        $cache_expire = $this->cache_expire;
        $max_age = $cache_expire * 60;
        $expires = gmdate(static::DATE_FORMAT, $time + $max_age);
        $cache_control = "public, max-age={$max_age}";
        $last_modified = gmdate(static::DATE_FORMAT, $time);

        return $response
            ->withAddedHeader('Expires', $expires)
            ->withAddedHeader('Cache-Control', $cache_control)
            ->withAddedHeader('Last-Modified', $last_modified);
    }

    private function attachPrivateCacheLimiterHeader(ResponseInterface $response, int $time): ResponseInterface
    {
        $response = $response->withAddedHeader('Expires', static::EXPIRED);

        return $this->attachPrivateNoExpireCacheLimiterHeader($response, $time);
    }

    private function attachPrivateNoExpireCacheLimiterHeader(ResponseInterface $response, int $time): ResponseInterface
    {
        $cache_expire = $this->cache_expire;
        $max_age = $cache_expire * 60;
        $cache_control = "private, max-age={$max_age}";
        $last_modified = gmdate(static::DATE_FORMAT, $time);

        return $response
            ->withAddedHeader('Cache-Control', $cache_control)
            ->withAddedHeader('Last-Modified', $last_modified);
    }

    private function attachSessionCookie(ResponseInterface $response, int $time): ResponseInterface
    {
        $options = $this->cookie_params;

        $cookie = urlencode($this->name).'='.$this->id;
        if ($options['lifetime'] > 0) {
            $expires = gmdate(static::DATE_FORMAT, $time + $options['lifetime']);
            $cookie .= "; expires={$expires}; max-age={$options['lifetime']}";
        }
        if ($options['path']) {
            $cookie .= "; path={$options['path']}";
        }
        if ($options['domain']) {
            $cookie .= "; domain={$options['domain']}";
        }
        if ($options['secure']) {
            $cookie .= '; secure';
        }
        if ($options['httponly']) {
            $cookie .= '; httponly';
        }

        return $response->withAddedHeader('set-cookie', $cookie);
    }
}
