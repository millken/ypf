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
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class Swoole implements LoggerAwareInterface
{
    private $server = null;
    protected $workers;
    protected static $instances = null;

    use LoggerAwareTrait;

    public function build()
    {
        $container = Application::getContainer();
        $swoole = $container->has('swoole') ? $container->get('swoole') : [];
        $address = $swoole['server']['address'] ?? '127.0.0.1';
        $port = $swoole['server']['port'] ?? $this->getRandomPort($address);
        $options = $swoole['options'] ?? [];
        $this->server = new SwooleHttpServer($address, $port, SWOOLE_PROCESS, SWOOLE_TCP);

        $this->server->set($options);
        $logger = Application::getContainer()->get('logger');
        $this->setLogger($logger);

        static::$instances = &$this;

        return $this;
    }

    public static function getInstance()
    {
        return static::$instances;
    }

    public static function getServer()
    {
        return static::$instances->server;
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
        // $this->logger->debug('task receive pid : {pid}, task_id : {task_id}, from_id= {from_id}, data= {data}', [
        //     'pid' => getmypid(),
        //     'task_id' => $task_id,
        //     'from_id' => $source,
        //     'data' => $data,
        //     ]);

        $task = SwooleSerialize::unpack($data);
        $unit = Application::getContainer()->get($task->getClass());
        $result = $unit->run($task->getPayload());
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
