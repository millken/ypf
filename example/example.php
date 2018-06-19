<?php

require './vendor/autoload.php';

use Ypf\Application\Factory\ApplicationFactory;
use Ypf\Interfaces\FactoryInterface;
use Psr\Http\Message\ResponseInterface;

$services = [
    FactoryInterface::class => ApplicationFactory::class,
    'Application' => [
        'address' => '', // The address on which to bind the app server. Binds on '0.0.0.0' if none is provided
        'port' => 1234, // The port on which the server to listen. A random one will be used if none is provided
        'options' => [// A list of options that is being passed directly to `Swoole\Http\Server::set()` for configuration
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
