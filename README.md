# ypf framework

## Overview
A micro php7 framework. in swoole mode, 10x performance can be improved.

The framework is mainly for the use of senior PHP programmers, keep everything under control.

Supports and uses various PSRs:

- PSR-2 (Coding Standard)
- PSR-4 (Autoloading)
- PSR-7 (HTTP Message)
- PSR-11 (Container Interface)
- PSR-15 (HTTP Middleware)

## Installion

```
composer require millken/ypf
```
## Requirements

1. PHP 7.2+
2. Swoole 2.0+ (Optional but recommended)

Swoole Http Server
```php

require './vendor/autoload.php';

use Ypf\Application\Factory\SwooleApplicationFactory;
use Ypf\Interfaces\FactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Views\PhpRenderer;

$services = [
    FactoryInterface::class => SwooleApplicationFactory::class,
    'swoole' => [
        'listen' => '*:7000',
        'options' => [
        ],
    ],
    'routes' => [
        [
            'pattern' => '/',
            'middleware' => [
                Middleware\RewriteMiddleware::class,
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
        ], [
            'pattern' => '/text{/{name}}?',
            'middleware' => [
                Controller\Text::class,
            ],
        ],
    ],
    'middleware' => [
    ],
    ResponseInterface::class => GuzzleHttp\Psr7\Response::class,
];

$services['db'] = function () {
    $config = [
        ...
    ];
    $db = new Ypf\Database\Connection($config);

    return $db;
};

$services[\Psr\Log\LoggerInterface::class] = function () {
    $logger = new Monolog\Logger('test');
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler('php://stdout', Monolog\Logger::DEBUG));

    return $logger;
};
$services['view'] = new PhpRenderer('./templates');

$container = new Ypf\Container($services);

$container->get(FactoryInterface::class)->run();

```

Swoole Worker
```php
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
            [Worker\CronTest::class, 10], //Every 10 seconds
            [Worker\CronTest::class, '* * * * *'],
        ]
    ],
];
$container = new Ypf\Container($services);

$container->get(FactoryInterface::class)->run();

```
See the full example https://github.com/millken/ypf_demo

## License

[Apache License, Version 2.0](https://github.com/millken/ypf/blob/master/license.txt)
