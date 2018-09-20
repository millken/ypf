<?php

declare(strict_types=1);

namespace Ypf\Controller;

use Ypf\Application;
use GuzzleHttp\Psr7\Response;

abstract class Controller
{
    public function __get($name)
    {
        return Application::getContainer()->get($name);
    }

    public static function isJson($value)
    {
        if (!is_string($value)) {
            return false;
        }

        json_decode($value);

        return is_string($value) && (json_last_error() === JSON_ERROR_NONE);
    }

    public function json($data)
    {
        $headers = [];
        $headers['content-type'] = 'application/json';
        $payload = static::isJson($data) ? $data : json_encode($data);

        return new Response(200, $headers, $payload);
    }

    public function response(int $status, array $headers, string $body)
    {
        return new Response($status, $headers, $body);
    }

    public function redirect($url, $status = 302)
    {
        return new Response($status, ['Location' => $url]);
    }
}
