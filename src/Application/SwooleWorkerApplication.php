<?php

declare(strict_types=1);

namespace Ypf\Application;

use Ypf\Interfaces\ApplicationInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;
use Swoole\Http\Server;
use Swoole\Process;
use Swoole\Table;
use Swoole\Timer;
use Swoole\Serialize;

class SwooleWorkerApplication implements ApplicationInterface, LoggerAwareInterface
{
    private static $inode;
    /** @var \Swoole\Http\Server */
    private $server;
    private static $isSpawnWorker;
    /** @var \ContainerInterface */
    private $container;

    use LoggerAwareTrait;

    public function __construct(ContainerInterface $container, Server $server)
    {
        $this->container = $container;
        $this->server = $server;
        static::$inode = getmyinode();
    }

    public function run(): void
    {
        $this->server->on('Start', [$this, 'onStart']);
        $this->server->on('ManagerStart', [$this, 'onManagerStart']);
        $this->server->on('Request', [$this, 'onRequest']);
        $this->server->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->server->on('PipeMessage', [$this, 'onPipeMessage']);
        $this->server->on('Task', [$this, 'onTask']);
        $this->server->on('Finish', [$this, 'onFinish']);
        $this->server->start();
    }

    public function onManagerStart(\Swoole\Http\Server $server): void
    {
        $name = sprintf('ypf-manager-%d', static::$inode);
        \swoole_set_process_name($name);
    }

    public function onStart(\Swoole\Http\Server $server): void
    {
        $name = sprintf('ypf-master-%d', static::$inode);
        \swoole_set_process_name($name);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
    }

    public function onRequest(\Swoole\Http\Request $request, \Swoole\Http\Response $response): void
    {
    }

    public function onFinish(Server $server, $task_id, $data): void
    {
    }

    public function onTask(Server $server, $task_id, $from_id, $data): void
    {
    }

    public function onPipeMessage(\Swoole\Http\Request $server, $worker_id, $data)
    {
        $server->task($data);
    }

    public function onWorkerStart(Server $server, int $worker_id): void
    {
        $worker = $this->container->get('worker');
        $name = sprintf('ypf-worker-%d', static::$inode);
        \swoole_set_process_name($name);
        if (!$worker_id && !static::$isSpawnWorker) {
            $container = $this->container;
            if (isset($worker['single']) && count($worker['single'])) {
                foreach ($worker['single'] as $single) {
                    $process = new Process(function (Process $process) use (&$container) {
                        $config = unserialize($process->pop());
                        $name = sprintf('ypf-worker-%d-%s', static::$inode, $config['worker']);
                        $process->name($name);
                        $obj = $container->get($config['worker']);
                        $obj->run($container);
                    }, false, 1);

                    $process->useQueue();
                    $pid = $process->start();
                    $process->push(serialize(array('worker' => $single)));
                }
            }

            $table = new Table(1024);
            $table->column('value', Table::TYPE_STRING, 65531);
            $table->create();
            if (isset($worker['cron']) && count($worker['cron'])) {
                $config = [];
                foreach ($worker['cron'] as $v) {
                    $data = Serialize::pack($v);
                    $config[md5($data)] = $v;
                }
                $table->set('_cron_queue', [
                    'value' => Serialize::pack($config),
                ]);
                $process = new Process(function (Process $process) use (&$table, &$container, $worker) {
                    Timer::tick(1000, function () use (&$table,&$container) {
                        $logger = $container->get(\Psr\Log\LoggerInterface::class);
                        $cron_queue = $table->exist('_cron_queue') ? Serialize::unpack($table->get('_cron_queue')['value']) : [];
                        $cron_ready = $table->exist('_cron_ready') ? Serialize::unpack($table->get('_cron_ready')['value']) : [];
                        foreach ($cron_queue as $name => $v) {
                            list($action, $cron_time) = $v;
                            $cron = \Cron\CronExpression::isValidExpression($cron_time);
                            if (!$cron) {
                                if (is_numeric($cron_time)) {
                                    $time_integer = intval($cron_time);
                                } else {
                                    $logger->error('cron time is Invalid', $v);
                                    continue;
                                }
                            } else {
                                $cron = \Cron\CronExpression::factory($cron_time);
                                $next_run_time = $cron->getNextRunDate()->getTimestamp();
                                $time_integer = intval($next_run_time - time());
                                $logger->debug(sprintf('next run time: %s, There are still %d seconds', date('Y-m-d H:i:s', $next_run_time), $time_integer), $v);
                            }
                            Timer::after(1000 * $time_integer, function () use (&$table, $name, $v, &$container) {
                                list($action, $cron_time) = $v;
                                register_shutdown_function(function () use ($container) {
                                    $logger = $container->get(\Psr\Log\LoggerInterface::class);
                                    $logger->error('shutdown', error_get_last());
                                });
                                go(function () use ($container, $action) {
                                    try {
                                        //$obj = $container->get($action);
                                        //call_user_func_array([$container->get($action), 'run'], [$container]);
                                        ob_start();
                                        eval('call_user_func_array([$container->get($action), "run"], [$container]);');
                                        $__capture__ = ob_get_contents();
                                        ob_end_clean();
                                        //$obj->run($container);
                                    } catch (\Exception $e) {
                                        $logger = $container->get(\Psr\Log\LoggerInterface::class);
                                        $logger->error($e->getMessage());
                                    }
                                });

                                if (isset($cron_ready[$name])) {
                                    unset($cron_ready[$name]);
                                    $table->set('_cron_ready', [
                                        'value' => Serialize::pack($cron_ready),
                                    ]);
                                }
                                $cron_queue[$name] = $v;
                                $table->set('_cron_queue', [
                                    'value' => Serialize::pack($cron_queue),
                                ]);
                            });
                            unset($cron_queue[$name]);
                            $cron_ready[$name] = $v;
                        }
                        $table->set('_cron_ready', [
                            'value' => Serialize::pack($cron_ready),
                        ]);
                        $table->set('_cron_queue', [
                            'value' => Serialize::pack($cron_queue),
                        ]);
                    });
                    $name = sprintf('ypfworker-%d-cron', static::$inode);
                    $process->name($name);
                }, false, 1);
                $process->start();
                $this->workers[$process->pid] = $process;
            }
            static::$isSpawnWorker = false;
        }
    }

    public function onReceive(int $pipe): void
    {
        if (array_key_exists($pipe, $this->pipes)) {
            $worker = $this->pipes[$pipe];
            echo str_replace(PHP_EOL, PHP_EOL.$worker->pid.' | ', $worker->read());
        }
    }
}
