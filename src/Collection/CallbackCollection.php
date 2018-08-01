<?php

declare(strict_types=1);

namespace Ypf\Collection;

class CallbackCollection extends Collection
{
    /** @var callable */
    private $callback;

    public function __construct(array $items, callable $callback)
    {
        parent::__construct($items);
        $this->callback = $callback;
    }

    public function current()
    {
        return call_user_func($this->callback, parent::current(), $this->key());
    }
}
