<?php

declare(strict_types=1);

namespace Worker;

use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;

class CronTest
{
    public function run(ContainerInterface $container): void
    {
        $logger = $container->get(LoggerInterface::class);

        $logger->error('test cron worker');
    }
}
