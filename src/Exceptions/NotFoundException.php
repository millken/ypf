<?php

declare(strict_types=1);

namespace Ypf\Exceptions;

/**
 * Interface NotFoundException.
 * Exception to indicate that a `404 not found`-type error occurred, while
 * performing the routing.
 */
interface NotFoundException extends \Throwable
{
}
