<?php

declare(strict_types=1);

namespace Ypf\Application;

use Swoole\Http\Server as SwooleHttpServer;
use Swoole\Http\Request as SwooleHttpRequest;
use Swoole\Http\Response as SwooleHttpResponse;
use Swoole\Serialize as SwooleSerialize;
use Ypf\Application;
use Ypf\Swoole\CronManager;
use GuzzleHttp\Psr7\ServerRequest;
use function swoole_set_process_name;

class Swoole
{
    private $server = null;
    protected $workers;
    protected static $instances = null;

    public function build()
    {
        $address = '127.0.0.1';
        $port = 7000;
        $this->server = new SwooleHttpServer($address, $port, SWOOLE_PROCESS, SWOOLE_TCP);

        static::$instances = &$this;

        return $this;
    }

    public static function getInstance()
    {
        return static::$instances;
    }

    public function onRequest(SwooleHttpRequest $request, SwooleHttpResponse $swooleResponse): void
    {
        $_SERVER = [];
        foreach ($request->server as $name => $value) {
            $_SERVER[strtoupper($name)] = $value;
        }
        foreach ($request->header as $name => $value) {
            $_SERVER[str_replace('-', '_', strtoupper('HTTP_'.$name))] = $value;
        }
        $_GET = $request->get ?? [];
        $_POST = $request->post ?? [];
        $_COOKIE = $request->cookie ?? [];
        $_FILES = $request->files ?? [];
        $content = $request->rawContent() ?: null;
        $headers = $request->header;
        $request = ServerRequest::fromGlobals();
        foreach ($headers as $header => $line) {
            $request = $request->withHeader($header, $line);
        }
        $request = $request->withAttribute('rawContent', $content);

        $response = Application::getInstance()->handleRequest($request);

        $status = $response->getStatusCode();
        $swooleResponse->status($response->getStatusCode());
        foreach ($response->getHeaders() as $header => $values) {
            $swooleResponse->header($header, $response->getHeaderLine($header));
        }
        $swooleResponse->end($response->getBody());
    }

    public function onTask(SwooleHttpServer $server, int $task_id, int $source, string $data)
    {
        //echo sprintf("pid=%d, task_id=%d, from_id=%d, data=%s\n", getmypid(), $task_id, $source, $data);

        $data = SwooleSerialize::unpack($data);
        if (!isset($data['name'])) {
            return;
        }
        $name = $data['name'];
        assert(
            $this->workers[$name],
            new \UnexpectedValueException("No task worker registered for '{$name}")
        );
        $result = $workers[$name]->run($data['payload']);
        $server->finish(SwooleSerialize::pack($result, 1));

        return $result;
    }

    public function onWorkerStart(SwooleHttpServer $server, $worker_id)
    {
        global $argv;
        if ($worker_id >= $server->setting['worker_num']) {
            $name = "php {$argv[0]}: task_worker_%d";
            $processName = sprintf($name, $worker_id);
        } else {
            if (!$worker_id && Application::getContainer()->has('workers')) {
                go(function () {
                    $cronManager = new CronManager();
                    $cronManager->process();
                });
            }
            $name = "php {$argv[0]}: worker_%d";
            $processName = sprintf($name, $worker_id);
        }
        swoole_set_process_name($processName);

        return true;
    }

    public function onStart(SwooleHttpServer $server)
    {
        global $argv;
        echo "Starting application server on {$server->host}:{$server->port}".PHP_EOL;
        $name = "php {$argv[0]}: master";
        swoole_set_process_name($name);

        return true;
    }

    public function onManagerStart(SwooleHttpServer $server)
    {
        global $argv;
        $name = "php {$argv[0]}: manager";
        swoole_set_process_name($name);

        return true;
    }

    public function run(): void
    {
        $this->server->on('finish', $this->server->onFinish ?? function () {
        });
        $this->server->on('task', [$this, 'onTask']);
        $this->server->on('request', [$this, 'onRequest']);
        $this->server->on('ManagerStart', [$this, 'onManagerStart']);
        $this->server->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->server->on('Start', [$this, 'onStart']);
        $this->server->start();
    }
}
