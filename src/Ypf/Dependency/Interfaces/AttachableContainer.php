<?php
declare(strict_types=1);
namespace Onion\Framework\Dependency\Interfaces;

use Psr\Container\ContainerInterface;

interface AttachableContainer extends ContainerInterface
{
    public function attach(ContainerInterface $container);
}
