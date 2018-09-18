<?php

declare(strict_types=1);

namespace Ypf\Swoole\Tasks;

class Task
{
    private $class;
    private $payload = null;
    private $callback;

    public function __construct(string $class, callable $callback = null)
    {
        $this->class = $class;
        $this->callback = $callback;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function withPayload($payload): void
    {
        $this->payload = $payload;
    }

    public function getPayload()
    {
        return $this->payload;
    }

    public function withCallback(callable $callback): void
    {
        $this->callback = $callback;
    }

    public function getCallback(): callable
    {
        return $this->callback ?? function () {
            // Nothing to do
        };
    }
}
