<?php
namespace Controller\Cli;

class Dns extends \Controller\Cli\Common {
	private $log;
	private $kafka;
	private $db;
	
	public function __construct() {
		parent::__construct();
		//log
		$this->log = new \Ypf\Lib\Log($this->config->get("dns.debug.log"));
		$this->log->SetLevel($this->config->get("dns.debug.level"));
	}
	
	public function Stats(){
		while (true) {
			$offset = intval(@file_get_contents($this->config->get("dns.kafka.tmp_pos_file")));
			$this->log->Info("start offset = $offset");
			$this->_dnstats($offset);
			usleep(200000);
		}
	}

	//获取域名主域名
	public function getPrimaryDomain($domain) {	
		if(strpos($domain, ':')) $domain = strstr(trim($domain), ':', true);
		$domains = explode('.', strtolower($domain));
		if(count($domains) > 2) {
		$exts = implode('.', array_slice($domains, -2)); 
		        if(in_array($exts, array('ac.cn', 'com.cn', 'com.au','com.sg', 'net.cn', 'gov.cn', 'org.cn', 'edu.cn','com.hk', 'net.hk', 'org.hk', 'net.in', 'co.uk'))) {
		                return $domains[count($domains)-3] . '.' . $exts;
		        }
		}
		$ext = array_pop($domains);
		
		if(in_array($ext, array('ac', 'ae', 'af', 'ag', 'ai', 'al', 'am', 'ao', 'aq', 'ar', 'as', 'asia', 'at', 'au', 'aw', 'ax', 'az', 'ba', 'bb', 'bd', 'be', 'bf', 'bg', 'bh', 'bi', 'biz', 'bj', 'bm', 'bn', 'bo', 'br', 'bs', 'bt', 'bw', 'by', 'bz', 'ca', 'cc', 'cd'/*, 'cf'*/, 'cg', 'ch', 'ci', 'ck', 'cl', 'cm', 'cn', 'cn', 'co', 'com', 'cr', 'cv', 'cw', 'cx', 'cy', 'cz', 'de', 'dj', 'dk', 'dm', 'do', 'dz', 'ec', 'edu', 'ee', 'eg', 'es', 'et', 'eu', 'fi', 'fj', 'fk', 'fm', 'fo', 'fr'/*, 'ga'*/, 'gd', 'ge', 'gf', 'gg', 'gh', 'gi', 'gl', 'gm', 'gn', 'gov', 'gp', 'gr', 'gs', 'gt', 'gu', 'gy', 'hk', 'hm', 'hn', 'hr', 'ht', 'hu', 'id', 'ie', 'il', 'im', 'in', 'info', 'io', 'iq', 'is', 'it', 'je', 'jm', 'jo', 'jp', 'ke', 'kg', 'kh', 'ki', 'kn', 'kr', 'kw', 'ky', 'kz', 'la', 'lb', 'lc', 'li', 'lk', 'lr', 'ls', 'lt', 'lu', 'lv', 'ly', 'ma', 'mc', 'md', 'me', 'mg', 'mk'/*, 'ml'*/, 'mm', 'mn', 'mo', 'mobi', 'mp', 'mq', 'mr', 'ms', 'mt', 'mu', 'mv', 'mw', 'mx', 'my', 'mz', 'na', 'name', 'nc', 'ne', 'net', 'nf', 'ng', 'ni', 'nl', 'no', 'np', 'nr', 'nu', 'nz', 'om', 'org', 'pa', 'pe', 'pf', 'pg', 'ph', 'pk', 'pl', 'pm', 'pn', 'pr', 'ps', 'pt', 'pw', 'py', 'qa', 're', 'ro', 'rs', 'ru', 'rw', 'sa', 'sb', 'sc', 'se', 'sg', 'sh', 'si', 'sk', 'sl', 'sm', 'sn', 'so', 'sr', 'st', 'su', 'sv', 'sx', 'sz', 'tc', 'td', 'tg', 'th', 'tj', /*'tk',*/ 'tl', 'tm', 'tn', 'to', 'tr', 'tt', 'tv', 'tw', 'tz', 'ua', 'ug', 'uk', 'us', 'uy', 'uz', 'vc', 've', 'vg', 'vi', 'vn', 'vn', 'vu', 'wf', 'ws', 'ye', 'yt', 'za', 'zm', 'zw', 'xyz'))) {
		        return array_pop($domains) . '.' . $ext;
		}
		return false;
	}    
    private function _dnstats($offset) {
    	if($offset == 0) $offset = \Kafka::OFFSET_BEGIN;
		$this->kafka = new \Kafka(
			$this->config->get("dns.kafka.host")/*,
			array(
				Kafka::LOGLEVEL         => Kafka::LOG_ON,//while in dev, default is Kafka::LOG_ON
				Kafka::CONFIRM_DELIVERY => Kafka::CONFIRM_OFF,//default is Kafka::CONFIRM_BASIC
				Kafka::RETRY_COUNT      => 1,//default is 3
				Kafka::RETRY_INTERVAL   => 25,//default is 100
			)*/
		);

		$topic = $this->config->get("dns.kafka.topic");
		//use it to OPTIONALLY specify a partition to consume from
		//if not, consuming IS slower. To set the partition:
		$this->kafka->setPartition(intval($this->config->get("dns.kafka.partition")));
		$msg = $this->kafka->consume($topic, $offset, 1000);
		if(empty($msg))return;
		$domains = array();
		foreach($msg as $offset => $mbody) {
	        list($index, $data) = explode("\n", $mbody);
	        $json = json_decode($data, true);
	        $time = strtotime($json['time']);
	        if($time <= 0) $time = time();
	        list($m, $d, $h) = explode("-", date("m-d-H", $time));
	        list($remote_ip, $server_ip) = explode("|", $json['remote_ip']);
	        $domain = substr($json['domain'], 0, -1);
	        $pridomain = $this->getPrimaryDomain($domain);
	        if(!isset($domains["$m-$d-$h-$server_ip"])) $domains["$m-$d-$h-$server_ip"] = array();
	        $domains["$m-$d-$h-$server_ip"][$pridomain]++;
			$this->log->Debug(__FILE__ . ":". __LINE__ . "\n" . $m.$d.$h . $domain);
		}
		file_put_contents($this->config->get("dns.kafka.tmp_pos_file"), $offset);
		$this->logtodb($domains);
		
    }
    
    public function logtodb($domains=array()) {
    /*
    	$domains = array(
    	"06-03-16-3.3.3.3" => array(
            "jsd.cc" => 8,
            "winktv321.com" => 1,
            "guotaigold.com" => 1,
        )

    	);
    	*/
		$this->db = new \Ypf\Lib\Database($this->config->get('dns.db'));
		foreach($domains as $date => $domain) {
			foreach($domain as $key => $value) {
				 list($m, $d, $h, $server_ip) = explode("-", $date);
				$record_exist = $this->db->table("query")
				->where("ip=? and domain=? and month=? and day=? and hour=?", $server_ip, $key, $m, $d, $h)
				->count();
				$this->log->Debug(__FILE__ . ":". __LINE__ . "\n". $this->db->getLastSql());
				if($record_exist == 0) {
					$data = array(
						'ip' => $server_ip,
						'domain' => $key,
						'nums' => $value,
						'month' => $m,
						'day' => $d,
						'hour' => $h,
					);
					$this->db->table('query')->save($data);
				}else{
					$this->db->update("update query set nums=nums+? where ip=? and domain=? and month=? and day=? and hour=?", array($value, $server_ip, $key, $m, $d, $h));
					$this->log->Debug(__FILE__ . ":". __LINE__ . "\n" . $this->db->getLastSql());
				}
				
			}
		}    
    }
	
}
