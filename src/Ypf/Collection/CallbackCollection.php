<?php

declare(strict_types=1);

namespace Ypf\Collection;

class CallbackCollection extends Collection
{
    /** @var callable */
    private $callback;

    public function __construct(iterable $items, callable $callback)
    {
        if (is_array($items)) {
            $items = new \ArrayIterator($items);
        }

        parent::__construct($items);
        $this->callback = $callback;
    }

    public function current()
    {
        return call_user_func($this->callback, parent::current(), $this->key());
    }
}
