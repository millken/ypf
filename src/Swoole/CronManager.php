<?php

declare(strict_types=1);

namespace Ypf\Swoole;

use Exception;
use ReflectionClass;
use Ypf\Application;
use Ypf\Controller\CronWorker;
use Cron\CronExpression;

class CronManager
{
    private $queue;
    private $job;

    public function __construct()
    {
        if (!class_exists(CronExpression::class)) {
            throw new Exception('please use composer require dragonmantank/cron-expression');
        }
    }

    public function process()
    {
        $workers = Application::getContainer()->get('workers');
        foreach ($workers as $worker) {
            $className = $worker[0];
            $classReflection = new ReflectionClass($className);
            if (!$classReflection->isSubclassOf(CronWorker::class)) {
                throw new Exception('cron worker mustbe extends \Ypf\Controller\\'.CronWorker::class);
            }
            if (!isset($worker[1])) {
                go(function () use ($classReflection) {
                    $classReflection->newInstance()->run();
                });
            } else {
                $this->queue[] = [$classReflection->newInstance(), $worker[1]];
            }
        }
        \swoole_timer_tick(1000, [$this, 'tick']);
    }

    public function tick()
    {
        $queue = $this->queue;
        foreach ($queue as $key => $val) {
            $crontab = CronExpression::isValidExpression($val[1]);
            if (!$crontab) {
                $timeSecond = intval($val[1]);
            } else {
                $cron = CronExpression::factory($val[1]);
                $nextRunTime = $cron->getNextRunDate()->getTimestamp();
                $timeSecond = intval($nextRunTime - time());
            }
            if ($timeSecond < 1) {
                continue;
            }

            \swoole_timer_after(1000 * $timeSecond, function () use ($key, $val) {
                $this->queue[$key] = $val;
                unset($this->job[$key]);
                go(function () use ($val) {$val[0]->run(); });
            });
            unset($this->queue[$key]);
            $this->job[$key] = $val;
        }
    }
}
