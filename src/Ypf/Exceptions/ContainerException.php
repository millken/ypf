<?php

namespace Ypf\Exceptions;

use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;

/**
 * Container Exception.
 */
class ContainerException extends InvalidArgumentException implements ContainerExceptionInterface
{
}
