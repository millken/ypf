<?php
namespace Cat\Cli;

class Test extends \Cat\Controller {
	private $log;
	private $urls = array(
		'http://www.google.com/',
		'http://www.facebook.com/',
		'http://www.v2ex.com/',
		'http://www.yahoo.com/',
		'http://www.qq.com/',
		'http://www.twitter.com',
	);
	public function __construct() {
		//log
		$this->log = new \Ypf\Lib\Log('./debug.log');
		$this->log->SetLevel(0);
	}

	public function index() {
		while (1) {
			$msg = date('Y-m-d H:i:s') . "===" . getmypid();
			$this->log->Info($msg);
			sleep(3);
		}
	}

	public function test4() {
		var_dump($this->memcache->set('t', date("Y-m-d H:i:s")));
		echo $this->memcache->get('t') . "\n";
		echo $this->memcache->getLastErrorCode();
	}

	public function test3() {
		$this->test4();
		$msg = sprintf("test 3 : %s pid= %d\n", date('Y-m-d H:i:s'), getmypid());
		echo $msg;
	}

	public function index2() {
		$this->thread->add(array("\Cat\Cli\Test", 't_1'), array(), array("\Cat\Cli\Test", 'r_1'));
	}

	public static function t_1($args = array()) {
		$msg = sprintf("%s t_1 >>> pid= %d\n", date('Y-m-d H:i:s'), getmypid());
		return $msg;
	}

	public static function r_1($task_id, $result) {
		$msg = date('Y-m-d H:i:s') . "r_1 <<<" . getmypid() . $result;
		echo $msg . "\n";
	}

	public function asynctest() {
		while (1) {
			$t = microtime(true);
			//$this->index2();
			$r = $this->thread->block(array("\Cat\Cli\Test", 'curl_get'), $this->urls, 8000);
			$tt = number_format((microtime(true) - $t), 4) . 's';
			$this->log->Info("async test curl 5 url time: " . $tt);
			sleep(5);
		}
	}

	public function synctest() {
		while (1) {
			//$this->index2();

			$t = microtime(true);
			foreach ($this->urls as $url) {
				$r = self::curl_get($url);
				//$this->log->Info($r);
			}
			$tt = number_format((microtime(true) - $t), 4) . 's';
			$this->log->Info("syntest curl 5url time: " . $tt);
			sleep(5);
		}
	}

	public static function curl_get($url) {
		$ch = \curl_init();
		//echo ($url);
		\curl_setopt($ch, CURLOPT_TIMEOUT, 5); //5秒超时
		\curl_setopt($ch, CURLOPT_URL, $url);
		\curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		\curl_setopt($ch, CURLOPT_HEADER, 0);
		$result = \curl_exec($ch);
		\curl_close($ch);
		$result = "res get";
		return $result;
	}
}
