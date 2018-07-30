<?php

require './vendor/autoload.php';

use Ypf\Application\Factory\ApplicationFactory;
use Ypf\Interfaces\FactoryInterface;
use Psr\Http\Message\ResponseInterface;

$services = [
    FactoryInterface::class => ApplicationFactory::class,
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
                Middleware\BenchmarkMiddleware::class,
                Controller\Greeter::class,
            ],
            'methods' => ['POST', 'GET', 'PUT'],
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
