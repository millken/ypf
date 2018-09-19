<?php

declare(strict_types=1);

namespace Ypf\Route\Exception;

class MethodNotAllowedException extends \Exception
{
    protected $allowedMethods = [];

    /**
     * MethodNotAllowedException constructor.
     *
     * @param array           $methods  The methods which are supported by the current route
     * @param int             $code
     * @param \Exception|null $previous
     */
    public function __construct($method, $code = 0, \Exception $previous = null)
    {
        parent::__construct('HTTP method not allowed', $code, $previous);
        $this->setAllowedMethods($method);
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

    public function setAllowedMethods(string $method): void
    {
        $this->allowedMethods = [$method];
    }
}
