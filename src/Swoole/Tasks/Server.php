<?php

declare (strict_types = 1);

namespace Ypf\Swoole\Tasks;

use Ypf\Application\Swoole as YAS;

class Server
{
    //task worker中不能推送
    /*
        $ts = new \Ypf\Swoole\Tasks\Server;
        $task = new \Ypf\Swoole\Tasks\Task(\App\Worker\Test::class, 'task1');
        $ts->push($task);
    */
    public function push(Task $task)
    {
        $payload = serialize($task);
        YAS::getServer()->task($payload, -1);
    }

    public function await(Task $task, float $timeout = 1)
    {
        $payload = serialize($task);

        return YAS::getServer()->taskwait($payload, (float) $timeout);
    }

    public function parallel(array $tasks, float $timeout = 10): array
    {
        $normalized = [];
        $results = [];
        foreach ($tasks as $idx => $task) {
            /* @var Task $task */
            $normalized[] = serialize($task);
            $results[$idx] = false;
        }
        $result = YAS::getServer()->taskWaitMulti($normalized, (float) $timeout);
        foreach ($result as $index => $value) {
            $results[$index] = $value;
        }

        return $results;
    }
}
