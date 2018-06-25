<?php declare(strict_types=1);
namespace Onion\Framework\Router\Exceptions;

use Onion\Framework\Router\Interfaces\Exception\NotFoundException as RouteNotFoundException;

/**
 * Class NotFoundException
 *
 * @package Onion\Framework\Router\Exceptions
 */
class NotFoundException extends \Exception implements RouteNotFoundException
{
}
