<?php
namespace Ypf\Swoole;
/**
 * 
 * 配置
 *
 */
class Task
{

	static function Task(\swoole_server $serv, int $task_id, int $from_id, string $data) {
		return 1;
	}
	
	static function Finish(\swoole_server $serv, int $task_id, string $data) {
	}
}

