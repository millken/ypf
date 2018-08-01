<?php

declare(strict_types=1);

namespace Ypf\Router\Exceptions;

use Ypf\Exceptions\NotFoundException as RouteNotFoundException;

/**
 * Class NotFoundException.
 */
class NotFoundException extends \Exception implements RouteNotFoundException
{
}
