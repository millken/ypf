<?php

declare(strict_types=1);

namespace Ypf\Dependency\Interfaces;

use Psr\Container\ContainerInterface as Container;

/**
 * Interface defining the required signature of a factory.
 * The container implementation enforces all factory
 * classes to implement this interface, in order to ensure
 * a common signature and to actually know how to produce
 * the object.
 */
interface FactoryInterface
{
    /**
     * Method that is called by the container, whenever a new
     * instance of the application is necessary. It is the only
     * method called when creating instances and thus, should
     * produce/return the fully configured object it is intended
     * to build.
     *
     * @param Container $container
     *
     * @return object
     */
    public function build(Container $container);
}
