<?php

declare(strict_types=1);

namespace Ypf\Interfaces;

use Psr\Http\Server\RequestHandlerInterface;

/**
 * A special-case interface allowing to distinguish
 * between normal middleware and an application
 * instance.
 */
interface ApplicationInterface extends RequestHandlerInterface
{
}
