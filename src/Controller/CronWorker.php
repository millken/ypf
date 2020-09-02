<?php

declare(strict_types=1);

namespace Ypf\Controller;

use Ypf\Application;

abstract class CronWorker
{
    public function __get($name)
    {
        return Application::getContainer()->get($name);
    }

    abstract public function run();
}
