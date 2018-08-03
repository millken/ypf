<?php

declare(strict_types=1);

namespace Ypf\Router;

class StaticRoute extends Route
{
    public function isMatch(string $path): bool
    {
        return $this->getPattern() === $path;
    }
}
