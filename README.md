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

```sh
composer require millken/ypf
```

## Requirements

1. PHP 7.2+
2. Swoole 4.2+ (Optional but recommended)

## Usage

> php swoole.php

```php
//swoole.php

require './vendor/autoload.php';
use GuzzleHttp\Psr7\Response;

$router = new Ypf\Route\Router();
$router->map('GET', '/', function ($request) {
    return 'test';
});
$router->map('GET', '/hello/{name}?', function ($request) {
    $name = ucwords($request->getAttribute('name', 'World!'));

    return new Response(200, [], 'hello '.$name);
});

$services = [
    'factory' => Ypf\Application\Swoole::class,

    'swoole' => [
        'server' => [
            'address' => '127.0.0.1',
            'port' => 7000,
        ],
        'options' => [
            'dispatch_mode' => 1,
        ],
    ],
    'middleware' => [
        new Ypf\Route\Middleware($router),
    ],
];

$app = new Ypf\Application($services);

$app->run();
```

> php -S 127.0.0.1:7000 cgi.php #cgi mode

```php
//swoole.php

require './vendor/autoload.php';
use GuzzleHttp\Psr7\Response;

$router = new Ypf\Route\Router();
$router->map('GET', '/', function ($request) {
    return 'test';
});
$router->map('GET', '/hello/{name}?', function ($request) {
    $name = ucwords($request->getAttribute('name', 'World!'));

    return new Response(200, [], 'hello '.$name);
});

$services = [
    'middleware' => [
        new Ypf\Route\Middleware($router),
    ],
];

$app = new Ypf\Application($services);

$app->run();
```

See the full [example](https://github.com/millken/ypf_demo)

## License

[Apache License, Version 2.0](https://github.com/millken/ypf/blob/master/license.txt)
