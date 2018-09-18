<?php

declare(strict_types=1);

namespace Ypf\Swoole\Tasks;

use Swoole\Server as SwooleServer;
use Ypf\Application\Swoole as YAS;
use Swoole\Serialize;

class Server
{
    public function push(Task $task)
    {
        $payload = Serialize::pack($task, 1);
        YAS::getServer()->task(
            $payload,
            -1,
            function (SwooleServer $server, $source, $data) use ($task) {
                call_user_func($task->getCallback(), Serialize::unpack($data));
            }
        );
    }

    public function await(Task $task, float $timeout = 1)
    {
        $payload = Serialize::pack($task, 1);

        return YAS::getServer()->taskwait($payload, (float) $timeout);
    }

    public function parallel(array $tasks, float $timeout = 10): array
    {
        $normalized = [];
        $results = [];
        foreach ($tasks as $idx => $task) {
            /* @var Task $task */
            $normalized[] = Serialize::pack($task, 1);
            $results[$idx] = false;
        }
        $result = YAS::getServer()->taskWaitMulti($normalized, (float) $timeout);
        foreach ($result as $index => $value) {
            $results[$index] = $value;
        }

        return $results;
    }
}
