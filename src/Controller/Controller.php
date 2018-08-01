<?php

declare(strict_types=1);

namespace Ypf\Controller;

use Psr\Http\Server\MiddlewareInterface;
use Ypf\Base;

abstract class Controller extends Base implements MiddlewareInterface
{
}
