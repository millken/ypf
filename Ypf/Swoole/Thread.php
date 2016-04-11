<?php
namespace Ypf\Swoole;

class Thread {
	private $cache;

	public function __construct($cache) {
		$this->cache = $cache;
	}

	public function add($func, $args = [], $callback = null, $thread = []) {

    	$data = [ 
    		'func' => $func,
    		'args' => $args,
    		'callback' => $callback,
    		'thread' => $thread,
    		];
        \Ypf\Swoole::getInstance()->getServer()->sendMessage(serialize($data), 0);
	}

	public function block($func, $args = [], $timeout = 7000) {
	   	$key = uniqid("ypf");
    	$total = count($args);
    	$data = []; 
		$data['tasks'] = $total;
		$this->cache->set($key, $data);

    	for($i=0; $i< $data['tasks']; $i++) {
    		$this->add($func, [$args[$i]], null, ['task' => $key, 'id' => $i]);
		}
		$n = 1;
		while(isset($data['tasks']) && $data["tasks"] > 0) {
			$result = $this->cache->get($key);
			$data = $result ? $result : ['tasks' => 0];
			$time = 200000 * $n++;
			if(($timeout * 1000) < $time) break;
    		usleep(200000);
    	}
    	$result = isset($data['results'])? $data['results'] : false;
		$this->cache->del($key);
    	return $result;	
	}

}
