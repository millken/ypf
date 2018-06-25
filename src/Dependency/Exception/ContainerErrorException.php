<?php

declare(strict_types=1);

namespace Ypf\Dependency\Exception;

use Psr\Container\ContainerExceptionInterface;

/**
 * Class ContainerErrorException.
 */
class ContainerErrorException extends \Exception implements ContainerExceptionInterface
{
}
