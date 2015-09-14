<?php
namespace Controller\Cli;

class Cacher extends \Controller\Cli\Common {
	
	public function __construct() {
		parent::__construct();
	}
	
	private static function DB() {
		static $db;
		if(!$db) {
			$config = \Ypf\Lib\Config::getAll();
			$db = new \Ypf\Lib\Database($config['cacher']['db']);
		}
		return $db;
	}
	
	private static function LOG() {
		static $log;
		if(!$log) {
			$config = \Ypf\Lib\Config::getAll();
			$log = new \Ypf\Lib\Log($config['cacher']['debug']['log']);
			$log->SetLevel($config['cacher']['debug']['level']);
		}
		return $log;
	}

	private static function BEANSTALKD() {
		static $beans;
		if(!$beans) {
			$config = \Ypf\Lib\Config::getAll();
			require (__ROOT__ . '/Lib/Beanstalk.class.php');
			$beans = new \Socket_Beanstalk($config['cacher']['beanstalkd']);
		}
		return $beans;
	}
	
	public function Cleaner(){
		$config = \Ypf\Lib\Config::getAll();
		\Ypf\Swoole\Task::tick($config['cacher']['worker']['period'], array("\Controller\Cli\Cacher", 'CleanCdnCache'));
	}
		
    public static function CleanCdnCache() {
    	$config = \Ypf\Lib\Config::getAll();
		self::BEANSTALKD()->useTube($config['cacher']['beanstalkd']['name']);
		for($i=0;$i<$config['cacher']['worker']['try_num']; ++$i) {
			$peek = self::BEANSTALKD()->peekReady();
			if($peek){
				$pBody = $peek['body'];
				$json = json_decode($pBody, true);
		    	$ips = self::DB()->select("SELECT s.ip FROM attribute a,server_ip s WHERE a.type = 'server' AND a.`use` & 32 AND a.pid = s.id AND s.pid = 0 order by s.id desc");
		    	$iplist = array_column($ips, 'ip');
				self::LOG()->Debug("cdn iplist:". implode(",", $iplist));
				if(!isset($json['urls'])) {
					self::CleanWorker($iplist, $json['domain']);
				}else{
					foreach($json['urls'] as $url) {
						self::CleanWorker($iplist, $json['domain'], $url);
					}
				}
				
				self::LOG()->Debug($pBody);
				self::BEANSTALKD()->delete($peek['id']);
		    }else{
		    	break;
		    }
        }
		
    }
    
    public static function CleanWorker($ips, $domain, $url = null) {
    	foreach($ips as $ip) {
    		$querydata = array(
    			'name' => $domain,
    			'url' => $url,
    		);
    		$api_url = sprintf("http://%s/static/?%s", $ip, http_build_query($querydata));
    		
    		\Ypf\Swoole\Task::add(array("\Controller\Cli\Cacher", 'curl_get'), array($api_url));
    	}
    }
    
	public static function curl_get($url) {
		
		$ch = \curl_init();
		\curl_setopt($ch, CURLOPT_TIMEOUT, 5); //5秒超时
        \curl_setopt($ch, CURLOPT_URL, $url);
        \curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        \curl_setopt($ch, CURLOPT_HEADER,0);
        $result = curl_exec($ch);
        self::LOG()->Info(sprintf("[%d] %s",curl_getinfo($ch, CURLINFO_HTTP_CODE), $url));
		curl_close($ch);
		return $result;
	}    
}
