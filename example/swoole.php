<?php

require './vendor/autoload.php';

use Ypf\Application\Factory\SwooleApplicationFactory;
use Ypf\Interfaces\FactoryInterface;
use Psr\Http\Message\ResponseInterface;

$services = [
    FactoryInterface::class => SwooleApplicationFactory::class,
    'swoole' => [
        'listen' => '*:7000',
        'user' => 'nobody',
        'pid_file' => '/tmp/dash.pid',
        'master_process_name' => 'ycs-master',
        'manager_process_name' => 'ycs-manager',
        'worker_process_name' => 'ycs-worker-%d',
        'task_worker_process_name' => 'ycs-task-worker-%d',
        'options' => [
        ],
    ],
    'routes' => [
        [
            'pattern' => '/',
            'middleware' => [
                Controller\Index::class,
            ],
            'methods' => ['GET'],
        ], [
            'pattern' => '/greet{/{name}}?',
            'middleware' => [
                Controller\Greeter::class,
            ],
            'methods' => ['GET', 'PUT'],
        ],
    ],
    'middleware' => [
    ],
    ResponseInterface::class => GuzzleHttp\Psr7\Response::class,
];

$services['db'] = function () {};
    // monolog
$services[\Psr\Log\LoggerInterface::class] = function () {
    $logger = new Monolog\Logger('test');
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler('php://stdout', Monolog\Logger::WARNING));

    return $logger;
};

//echo \Psr\Http\Message\ResponseInterface::class;
$container = new Ypf\Container($services);

$container->get(FactoryInterface::class)->run();
