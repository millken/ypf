<?php
namespace Controller\Cli;

class Firewall extends \Controller\Cli\Common {
	private $log;
	private $mongo;
	private $ipdb;
	private $worker_name;
	
	public function __construct() {
		parent::__construct();

		$this->ipdb = new \Lib\Ipdb();
		$dbfile = __ROOT__.'/data/db.dat';	
		$this->ipdb->load($dbfile);
	}
	
	public function Stats($worker_name) {
		$this->worker_name = $worker_name;
		//log
		$this->log = new \Ypf\Lib\Log($this->config->get("$worker_name.debug.log"));
		$this->log->SetLevel($this->config->get("$worker_name.debug.level"));	
		
		while (true) {
			$attack_body = @file_get_contents($this->config->get("$worker_name.firewall.url"));	
			$attack_arr = json_decode($attack_body, true);
			$data = array();
			foreach((array)$attack_arr['onAttackingHosts'] as $at) {
				$tmp = array();
				$tmp['host'] = $at['name'];//攻击IP
				$tmp['src'] = $at['src']; //来源
				$tmp['attacktype'] = $at['attacktype'];//攻击类型
				$tmp['duration'] = $at['duration'];//攻击时长
				list($trafficin_0, $trafficin_1) = explode("/", $at['trafficin']); //进流量
				$tmp['trafficin_0'] = floatval($trafficin_0);
				$tmp['trafficin_1'] = floatval($trafficin_1);
				list($trafficout_0, $trafficout_1) = explode("/", $at['trafficout']); //出流量
				$tmp['trafficout_0'] = floatval($trafficout_0);
				$tmp['trafficout_1'] = floatval($trafficout_1);	
				list($packetsin_0, $packetsin_1) = explode("/", $at['packetsin']); //进包数
				$tmp['packetsin_0'] = floatval($packetsin_0);
				$tmp['packetsin_1'] = floatval($packetsin_1);	
				list($packetsout_0, $packetsout_1) = explode("/", $at['packetsout']); //出包数
				$tmp['packetsout_0'] = floatval($packetsout_0);
				$tmp['packetsout_1'] = floatval($packetsout_1);	
				list($synin_0, $synin_1) = explode("/", $at['synin']); //syn进包
				$tmp['synin_0'] = floatval($synin_0);
				$tmp['synin_1'] = floatval($synin_1);	
				list($synin_traffic_0, $synin_traffic_1) = explode("/", $at['synin_traffic']); //syn进流量
				$tmp['synin_traffic_0'] = floatval($synin_traffic_0);
				$tmp['synin_traffic_1'] = floatval($synin_traffic_1);	
				list($synout_0, $synout_1) = explode("/", $at['synout']); //syn出包
				$tmp['synout_0'] = floatval($synout_0);
				$tmp['synout_1'] = floatval($synout_1);	
				list($tcpin_0, $tcpin_1) = explode("/", $at['tcpin']); //tcp进包
				$tmp['tcpin_0'] = floatval($tcpin_0);
				$tmp['tcpin_1'] = floatval($tcpin_1);	
				list($tcpin_traffic_0, $tcpin_traffic_1) = explode("/", $at['tcpin_traffic']); //tcp进流量
				$tmp['tcpin_traffic_0'] = floatval($tcpin_traffic_0);
				$tmp['tcpin_traffic_1'] = floatval($tcpin_traffic_1);	
				list($tcpout_0, $tcpout_1) = explode("/", $at['tcpout']); //tcp出包
				$tmp['tcpout_0'] = floatval($tcpout_0);
			    $tmp['tcpout_1'] = floatval($tcpout_1);
				list($udpin_0, $udpin_1) = explode("/", $at['udpin']); //udp进包
				$tmp['udpin_0'] = floatval($udpin_0);
			    $tmp['udpin_1'] = floatval($udpin_1);
				list($udpin_traffic_0, $udpin_traffic_1) = explode("/", $at['udpin_traffic']); //udp进流量
				$tmp['udpin_traffic_0'] = floatval($udpin_traffic_0);
			    $tmp['udpin_traffic_1'] = floatval($udpin_traffic_1);
				list($udpout_0, $udpout_1) = explode("/", $at['udpout']); //udp出包
				$tmp['udpout_0'] = floatval($udpout_0);
			    $tmp['udpout_1'] = floatval($udpout_1);
				list($icmpin_0, $icmpin_1) = explode("/", $at['icmpin']); //icmp进包
				$tmp['icmpin_0'] = floatval($icmpin_0);
			    $tmp['icmpin_1'] = floatval($icmpin_1);
				list($icmpin_traffic_0, $icmpin_traffic_1) = explode("/", $at['icmpin_traffic']); //icmp进流量
				$tmp['icmpin_traffic_0'] = floatval($icmpin_traffic_0);
			    $tmp['icmpin_traffic_1'] = floatval($icmpin_traffic_1);
				list($icmpout_0, $icmpout_1) = explode("/", $at['icmpout']); //icmp出包
				$tmp['icmpout_0'] = floatval($icmpout_0);
			    $tmp['icmpout_1'] = floatval($icmpout_1);
				list($fragin_0, $fragin_1) = explode("/", $at['fragin']); //frag进包
				$tmp['fragin_0'] = floatval($fragin_0);
			    $tmp['fragin_1'] = floatval($fragin_1);
				list($fragin_traffic_0, $fragin_traffic_1) = explode("/", $at['fragin_traffic']); //frag进流量
				$tmp['fragin_traffic_0'] = floatval($fragin_traffic_0);
			    $tmp['fragin_traffic_1'] = floatval($fragin_traffic_1);
				list($fragout_0, $fragout_1) = explode("/", $at['fragout']); //frag出包
				$tmp['fragout_0'] = floatval($fragout_0);
			    $tmp['fragout_1'] = floatval($fragout_1);
				list($otherin_0, $otherin_1) = explode("/", $at['otherin']); //other进包
				$tmp['otherin_0'] = floatval($otherin_0);
			    $tmp['otherin_1'] = floatval($otherin_1);
				list($otherin_traffic_0, $otherin_traffic_1) = explode("/", $at['otherin_traffic']); //other进流量
				$tmp['otherin_traffic_0'] = floatval($otherin_traffic_0);
			    $tmp['otherin_traffic_1'] = floatval($otherin_traffic_1);
				list($otherout_0, $otherout_1) = explode("/", $at['otherout']); //other出包
				$tmp['otherout_0'] = floatval($otherout_0);
			    $tmp['otherout_1'] = floatval($otherout_1);
				list($dnsin_0, $dnsin_1) = explode("/", $at['dnsin']); //dns进包
				$tmp['dnsin_0'] = floatval($dnsin_0);
			    $tmp['dnsin_1'] = floatval($dnsin_1);
				list($dnsin_traffic_0, $dnsin_traffic_1) = explode("/", $at['dnsin_traffic']); //dns进流量
				$tmp['dnsin_traffic_0'] = floatval($dnsin_traffic_0);
			    $tmp['dnsin_traffic_1'] = floatval($dnsin_traffic_1);
				list($dnsout_0, $dnsout_1) = explode("/", $at['dnsout']); //dns出包
				$tmp['dnsout_0'] = floatval($dnsout_0);
			    $tmp['dnsout_1'] = floatval($dnsout_1);
				list($links_0, $links_1) = explode("/", $at['links']); //连接数
				$tmp['links_0'] = floatval($links_0);
			    $tmp['links_1'] = floatval($links_1);
				list($tcplinks_0, $tcplinks_1) = explode("/", $at['tcplinks']); //tcp连接数
				$tmp['tcplinks_0'] = floatval($tcplinks_0);
			    $tmp['tcplinks_1'] = floatval($tcplinks_1);
				list($udplinks_0, $udplinks_1) = explode("/", $at['udplinks']); //udp连接数
				$tmp['udplinks_0'] = floatval($udplinks_0);
			    $tmp['udplinks_1'] = floatval($udplinks_1);
			    $tmp['timestamp'] = new \MongoDate(time());
			    $data[] = $tmp;		    			    
			}
 	 	$this->log->Debug(__FILE__ . ":". __LINE__ . "\n" . $attack_body);
 	 	if($data) {
			$this->mongo = new \MongoClient($this->config->get("$worker_name.mongo.db"));
			$table = sprintf($this->config->get("$worker_name.mongo.tablename"), date("Y"), date("m"));
			$m = $this->mongo->selectCollection($this->config->get("$worker_name.mongo.dbname"), $table);
			$m->batchInsert($data);
		}
		sleep($this->config->get("$worker_name.firewall.period"));			
		}
	}
	
	
}
