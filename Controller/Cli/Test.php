<?php
namespace Controller\Cli;

class Test extends \Controller\Cli\Common {
	private $log;
	
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

	}
	
	
	public function synctest() {
	while( 1 ) {
		$urls = array(
			'http://www.google.com/',
			'http://www.facebook.com/',
			'http://www.v2ex.com/',
			'http://www.yahoo.com/',
			'http://www.qq.com/'
		);
		$t = microtime(true);
		foreach($urls as $url) {
			$r = $this->curl_get($url);
			//$this->log->Info($r);
		}
		$tt = number_format((microtime(true)-$t),4).'s';
		$this->log->Info("syntest curl 5url time: " . $tt);
		sleep(10);
		}
	}
	
	private function curl_get($url) {
		
		$ch = \curl_init();
		if (!$ch) {
			exit('内部错误：服务器不支持CURL');
		}
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
