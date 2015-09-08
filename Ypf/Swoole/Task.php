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
    	$config = \Ypf\Lib\Config::getAll();

    	$data = array(
    		'func' => $func,
    		'args' => $args,
    		'callback' => $callback
    		);
    	print_r($data);
    	//call_user_func(array($config["swoole"]["serv"], 'task'), serialize($data));
        $config["swoole"]["serv"]->task(serialize($data));
    }

	public static function task($serv, $task_id, $from_id, $data) {
		echo sprintf("task_id=%d, from_id=%d, data=%s", $task_id, $from_id, $data);
		$data = unserialize($data);
		$result = call_user_func_array($data["func"], $data["args"]);
		$results = array('callback' => $data['callback'], 'result' => $result);
		$serv->finish(serialize($results));

	}
	
	public static function finish($serv, $task_id, $data) {
		echo "ffffffffffff>>" . $data;
	}
}

