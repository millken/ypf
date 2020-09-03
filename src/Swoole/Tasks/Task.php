<?php

declare(strict_types=1);

namespace Ypf\Swoole\Tasks;

class Task
{
    private $class;
    private $parameter = null;
    private $method;

    public function __construct(string $class, string $method = null)
    {
        $this->class = $class;
        $this->method = $method;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function withParameter($parameter): void
    {
        $this->parameter = $parameter;
    }

    public function getParameter()
    {
        return $this->parameter;
    }

    public function withMethod(string $method): void
    {
        $this->method = $method;
    }

    public function getMethod(): string
    {
        return $this->method;
    }
}
