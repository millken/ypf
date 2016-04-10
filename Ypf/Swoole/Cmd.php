<?php

namespace Ypf\Swoole;

class Cmd {
	static $serverConfig;
	static $masterPidFile;
	static $masterPid;
	public static function start($serverConfig) {
		global $argv;
		self::$serverConfig = $serverConfig;
		self::$masterPidFile = isset(self::$serverConfig['server']['pid_file']) ? 
    	self::$serverConfig['server']['pid_file'] : '/tmp/ypf.pid';
    	self::$masterPid = @file_get_contents(self::$masterPidFile);
		self::envCheck();
		if(empty($argv[1])) {
			self::usage();
		}
		switch ($argv[1]) {
			case 'start':
				echo "server starting ...".PHP_EOL;
				break;
			case 'reload':
				self::reload();
				break;
			case 'stop':
				self::stop();exit;
				break;
			case 'restart':
				self::restart();
				break;
			case 'kill':
				self::kill();
				break;
			default:
				self::usage();
				break;
		}
	}

	public static function envCheck() {
		if(!extension_loaded('swoole')) {
			exit("swoole extension must be installed: https://github.com/swoole/swoole-src\n");
		}
	}

	public static function kill() {
	    $rets = $match = array();

		$process_lists = [
		(isset(self::$serverConfig['server']['master_process_name']) ? 
    	self::$serverConfig['server']['master_process_name'] : 'ypf:swoole-master'),
		(isset(self::$serverConfig['server']['worker_process_name']) ? 
    	self::$serverConfig['server']['worker_process_name'] : 'ypf:swoole-worker-%d'),
		(isset(self::$serverConfig['server']['task_worker_process_name']) ? 
    	self::$serverConfig['server']['task_worker_process_name'] : 'ypf:swoole-task-worker-%d')
    	];	
    	foreach($process_lists as $i => $process_name) {
    		$process_name = str_replace("%d", "", $process_name);
	    	exec("ps aux | grep -E '".$process_name."' | grep -v grep", $rets[$i]);
		}
	    $this_pid = posix_getpid();
	    $this_ppid = posix_getppid();
	    foreach($rets as $ret)
	    foreach($ret as $line) {
	        if(preg_match("/^[\S]+\s+(\d+)\s+/", $line, $match)) {
	            $tmp_pid = $match[1];
	            if($this_pid != $tmp_pid && $this_ppid != $tmp_pid) {
	                posix_kill($tmp_pid, 9);
	            }
	        }
	    }
	    exit("server killed ...".PHP_EOL);
	}

	public static function restart() {
		self::stop();	
		while(is_file(self::$masterPidFile)) {
			$masterPid = @file_get_contents(self::$masterPidFile);
			if(self::$masterPid <> $masterPid) break;
    		usleep(200000);
    	}
    	echo "server restarting ...".PHP_EOL;
	}

	public static function reload() {
    	if(is_numeric(self::$masterPid)) {
    		posix_kill(self::$masterPid, 10);
    		echo "server reload ...".PHP_EOL;
    	}else{
    		echo "server not running ...".PHP_EOL;
    	}
    	exit;
	}

	public static function stop() {
    	if(is_numeric(self::$masterPid)) {
    		posix_kill(self::$masterPid, 15 );
    		echo "server stoping ...".PHP_EOL;
    	}else{
    		echo "server not running ...".PHP_EOL;
    	}
	}

	public static function usage() {
		global $argv;
	    echo "Usage: {$argv[0]} {start|stop|restart|reload|kill|status}".PHP_EOL;
	    exit;
	}
}