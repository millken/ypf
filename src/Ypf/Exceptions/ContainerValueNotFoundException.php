<?php

declare(strict_types=1);

namespace Ypf\Exceptions;

use RuntimeException;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Class ContainerValueNotFoundException.
 */
class ContainerValueNotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
}
