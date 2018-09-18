<?php

declare(strict_types=1);

namespace Ypf\Swoole;

use Ypf\Application;
use Swoole\Process as SwooleProcess;

class CronManager
{
    private $queue;
    private $ready;

    public function process()
    {
        echo 'cronmanager';
        $process = new SwooleProcess([$this, 'start'], false, 1);
        $pid = $process->start();
    }

    public function start(SwooleProcess $worker)
    {
        global $argv;
        $processName = "php {$argv[0]}: cron manager";
        \swoole_set_process_name($processName);
        $this->queue = Application::getContainer()->get('workers');
        \swoole_timer_tick(1000, [$this, 'tick']);
    }

    public function tick()
    {
        $queue = $this->queue;
        foreach ($queue as $key => $val) {
            \swoole_timer_tick(1000 * $val['cron'], function () use ($key, $val) {
                $this->queue[$key] = $val;
                unset($this->ready[$key]);
                $obj = new $val['class']();
                $obj->$val['method']();
            });
            unset($this->queue[$key]);
            $this->ready[$key] = $val;
        }
        echo 'tick'.PHP_EOL;
    }
}
