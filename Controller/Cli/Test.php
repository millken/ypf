<?php
namespace Controller\Cli;

class Test extends \Controller\Cli\Common {
	private $log;
	private $urls = array(
			'http://www.google.com/',
			'http://www.facebook.com/',
			'http://www.v2ex.com/',
			'http://www.yahoo.com/',
			'http://www.qq.com/'
		);
	public function __construct() {
		//log
		$this->log = new \Ypf\Lib\Log('./debug.log');
		$this->log->SetLevel(0);
	}
	
	public function index(){
        while( 1 ) {
            $msg =  date('Y-m-d H:i:s') . "===" . getmypid();
            $this->log->Info($msg);
            sleep(3);
        }
	}
    
	public function index2(){
		\Ypf\Swoole\Task::add(array("\Controller\Cli\Test", 't_1') , array(), array("\Controller\Cli\Test", 'r_1'));
	}
	
	public static function t_1 ($args = array()) {
		$msg =  sprintf("%s t_1 >>> pid= %d, args = %s\n", date('Y-m-d H:i:s'), getmypid(), print_r($args, true));
		echo $msg;
		return $msg;
	}

	public static function r_1 () {
		$msg =  date('Y-m-d H:i:s') . "r_1 <<<" . getmypid();
		echo $msg;
	}
	
	public function asynctest() {
		echo "start async\n";
		$r = \Ypf\Swoole\Task::thread($this->urls, array("\Controller\Cli\Test", 't_1'));
		print_r($r);
		echo "end async\n";
	}
		
	
	public function synctest() {
	while( 1 ) {
		$this->index2();

		$t = microtime(true);
		foreach($this->urls as $url) {
			$r = self::curl_get($url);
			//$this->log->Info($r);
		}
		$tt = number_format((microtime(true)-$t),4).'s';
		$this->log->Info("syntest curl 5url time: " . $tt);
		sleep(10);
		}
	}
	
	public static function curl_get($url) {
		
		$ch = \curl_init();
		//echo ($url);
		\curl_setopt($ch, CURLOPT_TIMEOUT, 5); //5秒超时
        \curl_setopt($ch, CURLOPT_URL, $url);
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        \curl_setopt($ch, CURLOPT_HEADER,0);
        $result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}	
}
