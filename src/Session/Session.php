<?php

declare(strict_types=1);

namespace Ypf\Session;

class Session
{
    const FLASH = '::flash::';

    private $data;
    private $flash;

    public function __construct(array &$data)
    {
        $this->data = &$data;
        $this->flash = $this->data[self::FLASH] ?? [];
        unset($this->data[self::FLASH]);
    }

    public function all(): array
    {
        $data = array_merge($this->data, $this->data[self::FLASH] ?? []);
        unset($data[self::FLASH]);
        $previous = array_diff_key($this->flash, $data);

        return array_merge($data, $previous);
    }

    public function has(string $key): bool
    {
        return isset($this->flash[$key])
            || isset($this->data[$key])
            || isset($this->data[self::FLASH][$key]);
    }

    public function get(string $key, $default = null)
    {
        return $this->data[$key]
            ?? $this->data[self::FLASH][$key]
            ?? $this->flash[$key]
            ?? $default;
    }

    public function set(string $key, $value)
    {
        unset($this->data[self::FLASH][$key]);
        $this->data[$key] = $value;
    }

    public function flash(string $key, $value)
    {
        unset($this->data[$key]);
        $this->data[self::FLASH][$key] = $value;
    }

    public function unset(string $key)
    {
        unset($this->flash[$key]);
        unset($this->data[$key]);
        unset($this->data[self::FLASH][$key]);
    }

    public function delete()
    {
        foreach (array_keys($this->data) as $key) {
            unset($this->data[$key]);
        }
        foreach (array_keys($this->flash) as $key) {
            unset($this->flash[$key]);
        }
    }
}
