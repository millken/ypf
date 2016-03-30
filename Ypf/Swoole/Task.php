<?php
namespace Ypf\Swoole;
/**
 * 
 * 配置
 *
 */
class Task
{
    public static $tasks = array();

    /**
     * 
     * 添加一个任务
     * 
     * @param callback $func 任务运行的函数或方法
     * @param mix $args 任务运行的函数或方法使用的参数
     * @return void
     */
    public static function add($func, $args = array(), $callback = null, $thread = array()) {
    	$config = \Ypf\Lib\Config::getAll();

    	$data = array(
    		'func' => $func,
    		'args' => $args,
    		'callback' => $callback,
    		'thread' => $thread,
    		);
    	//call_user_func(array($config["swoole"]["serv"], 'task'), serialize($data));
        $config["swoole"]["serv"]->task(serialize($data));
    }
    
    public static function set($key, $data) {
    	$file = "/dev/shm/" . $key;
    	file_put_contents($file, serialize($data), LOCK_EX);
    }
    
    public static function get($key) {
    	$file = "/dev/shm/" . $key;
    	if(!is_file($file))return null;
    	$data = file_get_contents($file);
    	return unserialize($data);
    }
    
    public static function del($key) {
	    $file = "/dev/shm/" . $key;
	    if(is_file($file)) unlink($file);
    }

	/**
	 *
	 * 添加一个定时器
	 *
	 */
	public static function tick($ms, $callback, $param = null) {
		$config = \Ypf\Lib\Config::getAll();
		$config["swoole"]["serv"]->tick($ms, $callback, $param);
	}

	/**
	 *
	 * 多线程并发阻塞获取
	 *
	 */	
    public static function thread($jobs = array(), $func, $timeout= 7.0) {
    	$unid = uniqid("ypf");
    	$total_jobs = count($jobs);
    	$data = array();
    	$data['tasks'] = $total_jobs;
    	self::set($unid, $data);
    	for($i=0; $i< $data['tasks']; $i++) {
    		self::add($func, array($jobs[$i]), null, array('task' => $unid, 'id' => $i));
    	}
    	while($data['tasks']) {
    		$data = self::get($unid);
    		usleep(200000);
    	}
    	$result = $data['results'];
    	self::del($unid);
    	return $result;
    }

	//task worker
	public static function task($serv, $task_id, $from_id, $data) {
		//echo sprintf("pid=%d, task_id=%d, from_id=%d, data=%s\n", getmypid(), $task_id, $from_id, $data);
		$data = unserialize($data);
		$result = call_user_func_array($data["func"], $data["args"]);
		$results = array('callback' => $data['callback'], 'result' => $result, 'thread' => $data['thread']);
		//$serv->finish(serialize($results));
		return $results;
	}
	
	public static function finish($serv, $task_id, $data) {
		if(!empty($data['thread'])) {
			$key = $data['thread']['task'];
			$value = self::get($key);
			$value['results'][$data['thread']['id']] = $data['result'];
			$value['tasks'] -= 1;
			self::set($key, $value);
		}
		//echo sprintf("pid = %d fnish = %s, value = %s\n",getmypid(), print_r($data, true), print_r($value, true));
		if($data['callback']) {
			$data["args"] = array('task_id' => $task_id, 'result'=> $data['result']);
			call_user_func_array($data["callback"], $data["args"]);
		}
	}
}

