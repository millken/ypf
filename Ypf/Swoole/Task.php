<?php
namespace Ypf\Swoole;
/**
 * 
 * 配置
 *
 */
class Task
{
    protected static $tasks = array();
    static $serv = null;

    public static function setServe($serv)
    {
    	self::$serv = $serv;
    	call_user_func(array(&self::$serv, 'task'), serialize($data));
    }
    /**
     * 
     * 添加一个任务
     * 
     * @param callback $func 任务运行的函数或方法
     * @param mix $args 任务运行的函数或方法使用的参数
     * @return void
     */
    public static function add($func, $args = array(), $callback)
    {
    	print_r(self::$serv);
    	$data = array(
    		'func' => $func,
    		'args' => $args,
    		'callback' => $callback
    		);
    	//call_user_func(array(&self::$serv, 'task'), serialize($data));
        self::$serv->task(serialize($data));
    }

	public static function task(\swoole_server $serv, int $task_id, int $from_id, string $data) {
		echo sprintf("task_id=%d, from_id=%d, data=%s", $task_id, $from_id, $data);
		return 1;
	}
	
	public static function finish(\swoole_server $serv, int $task_id, string $data) {
	}
}

