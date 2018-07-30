<?php

declare(strict_types=1);

namespace Worker;

use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;

class SingleTest
{
    public function run(ContainerInterface $container): void
    {
        $logger = $container->get(LoggerInterface::class);

        while (true) {
            $logger->error('test single worker');
            sleep(3);
        }
    }
}
