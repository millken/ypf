<?php

declare(strict_types=1);

namespace Ypf\Router\Interfaces\Exception;

/**
 * Class NotAllowedException
 * Exception to indicate that a route with pattern that matches the requested
 * one exists, but does not indicate that it supports the currently requested
 * method.
 */
interface NotAllowedException extends \Throwable
{
    /**
     * Sets the methods that ARE supported by the method.
     *
     * @param iterable $methods
     */
    public function setAllowedMethods(iterable $methods): void;

    /**
     * Returns the list of methods supported by the route.
     *
     * @return iterable
     */
    public function getAllowedMethods(): iterable;
}
