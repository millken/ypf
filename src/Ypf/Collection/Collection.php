<?php

declare(strict_types=1);

namespace Ypf\Collection;

class Collection implements \Iterator
{
    /** @var \Iterator */
    private $items;

    public function __construct(iterable $items)
    {
        if (is_array($items)) {
            $items = new \ArrayIterator($items);
        }
        $this->items = $items;
    }

    public function current()
    {
        return $this->items->current();
    }

    public function key()
    {
        return $this->items->key();
    }

    public function next(): void
    {
        $this->items->next();
    }

    public function rewind(): void
    {
        $this->items->rewind();
    }

    public function valid(): bool
    {
        return $this->items->valid();
    }

    public function setFilter(callable $callback): void
    {
        $this->items = new \CallbackFilterIterator($this->items, $callback);
    }
}
