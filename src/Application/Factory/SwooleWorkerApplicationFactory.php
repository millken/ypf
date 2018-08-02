<?php

declare(strict_types=1);

namespace Ypf\Application\Factory;

use Ypf\Application\SwooleWorkerApplication;
use Ypf\Interfaces\FactoryInterface;
use Ypf\Log\VoidLogger;
use Psr\Container\ContainerInterface;

final class SwooleWorkerApplicationFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     *
     * @return Application
     */
    public function build(ContainerInterface $container)
    {
        $address = '127.0.0.1';
        $port = $this->getRandomPort($address);
        $server = new \Swoole\Http\Server($address, $port, SWOOLE_BASE, SWOOLE_TCP);
        $logger = $container->has(\Psr\Log\LoggerInterface::class) ?
        $container->get(\Psr\Log\LoggerInterface::class) : new VoidLogger();

        if (!$container->has('worker')) {
            $logger->error("'worker' not found in container service");
            exit;
        }
        $worker = $container->get('worker');
        isset($worker['options']) && $server->set($worker['options']);

        $app = new SwooleWorkerApplication($container, $server);
        $logger->info("Swoole HTTP Server listen: $address:$port");
        $app->setLogger($logger);

        return $app;
    }

    private function getRandomPort($address): int
    {
        while (true) {
            $port = mt_rand(1025, 65000);
            $fp = @fsockopen($address, $port, $errno, $errstr, 0.1);
            if (!$fp) {
                break;
            }
        }

        return $port;
    }
}
