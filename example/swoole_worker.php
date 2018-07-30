<?php

require './vendor/autoload.php';

use Ypf\Application\Factory\SwooleWorkerApplicationFactory;
use Ypf\Interfaces\FactoryInterface;

$services = [
    FactoryInterface::class => SwooleWorkerApplicationFactory::class,
    'worker' => [
        'single' => [
            Worker\SingleTest::class,
        ],
        'cron' => [
            [Worker\CronTest::class, '30 * * * *'],
            [Worker\CronTest::class, '* * * * *'],
        ],
        'options' => [
        ],
    ],
];

$services['db'] = function () {};
    // monolog
$services[\Psr\Log\LoggerInterface::class] = function () {
    $logger = new Monolog\Logger('test');
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler('php://stdout', Monolog\Logger::DEBUG));

    return $logger;
};

//echo \Psr\Http\Message\ResponseInterface::class;
$container = new Ypf\Container($services);

$container->get(FactoryInterface::class)->run();
