<?php

declare(strict_types=1);

namespace Ypf\Dependency\Exception;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Class UnknownDependency.
 */
class UnknownDependency extends \Exception implements NotFoundExceptionInterface
{
}
