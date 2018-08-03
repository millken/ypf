<?php

declare(strict_types=1);

namespace Ypf\Router\Exceptions;

use Ypf\Router\Interfaces\Exception\NotAllowedException;

/**
 * Class MethodNotAllowedException.
 */
class MethodNotAllowedException extends \Exception implements NotAllowedException
{
    protected $allowedMethods = [];

    /**
     * MethodNotAllowedException constructor.
     *
     * @param array           $methods  The methods which are supported by the current route
     * @param int             $code
     * @param \Exception|null $previous
     */
    public function __construct(iterable $methods, $code = 0, \Exception $previous = null)
    {
        parent::__construct('HTTP method not allowed', $code, $previous);
        $this->setAllowedMethods($methods);
    }

    /**
     * Returns the list of methods supported by the route.
     *
     * @return array
     */
    public function getAllowedMethods(): iterable
    {
        return $this->allowedMethods;
    }

    /**
     * Sets the methods that ARE supported by the method.
     *
     * @param array $methods
     */
    public function setAllowedMethods(iterable $methods): void
    {
        $this->allowedMethods = $methods;
    }
}
