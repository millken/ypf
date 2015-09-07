<?php
namespace Controller\Cli;

class Waf extends \Controller\Cli\Common {
	private $log;
	private $kafka;
	private $mongo;
	private $ipdb;
	
	public function __construct() {
		parent::__construct();
		//log
		$this->log = new \Ypf\Lib\Log($this->config->get("waf.debug.log"));
		$this->log->SetLevel($this->config->get("waf.debug.level"));

		$this->ipdb = new \Lib\Ipdb();
		$dbfile = __ROOT__.'/data/db.dat';	
		$this->ipdb->load($dbfile);			
	}
	
	public function Stats(){
		while (true) {
			$offset = intval(@file_get_contents($this->config->get("waf.kafka.tmp_pos_file")));
			$this->log->Info("waf start offset = $offset");
			$this->_stats($offset);
			sleep(5);
		}
	}

    private function _stats($offset) {
    	if($offset == 0) $offset = \Kafka::OFFSET_END;
		$this->kafka = new \Kafka(
			$this->config->get("waf.kafka.host")/*,
			array(
				Kafka::LOGLEVEL         => Kafka::LOG_ON,//while in dev, default is Kafka::LOG_ON
				Kafka::CONFIRM_DELIVERY => Kafka::CONFIRM_OFF,//default is Kafka::CONFIRM_BASIC
				Kafka::RETRY_COUNT      => 1,//default is 3
				Kafka::RETRY_INTERVAL   => 25,//default is 100
			)*/
		);
		$this->kafka->setPartition(intval($this->config->get("waf.kafka.partition")));

		$topic = $this->config->get("waf.kafka.topic");

		$batch_num = 0;
		$batch_data = array();
		while(true) {
				//$this->kafka->produce($topic, "message contentxxxx");
				$ret = $this->kafka->consume($topic, $offset, 1000);
		        $this->log->Debug(__FILE__ . ":". __LINE__ . print_r($ret, true));
		        if(empty($ret)) break;
		        foreach ($ret as $offset => $rval) {
			        list($index, $data) = explode("\n", $rval);
			        $index_arr = json_decode($index, true);
			        $data_arr = json_decode($data, true);

			        if(is_null($index_arr) or is_null($data_arr))break;
					$remote_ip = $data_arr['remote_ip'];
					$ipinfo = $this->ipdb->lookup($remote_ip);
					if(is_array($ipinfo)) {
						 $data_arr = array_merge( $data_arr, $ipinfo);
					}
					$data_arr['timestamp'] = new \MongoDate(strtotime($data_arr['addtime']));
			        $batch_data[$index_arr['index']['_index']][] = $data_arr;
			        ++$batch_num;
		        }

		        $this->log->Debug(__FILE__ . ":". __LINE__ . print_r($data_arr, true));

		        if($batch_num >= $this->config->get("waf.mong.batch_num")) {
		        	if(!empty($batch_data))$this->_mongowork($batch_data);
		        	$batch_num = 0;
		        	$batch_data = array();
		        }
		}

		file_put_contents($this->config->get("waf.kafka.tmp_pos_file"), $offset);
		if(!empty($batch_data))$this->_mongowork($batch_data);
		
    }

	private function _mongowork($data) {
		$this->log->Debug(__FILE__ . ":". __LINE__ . print_r($data, true));
		$this->mongo = new \MongoClient($this->config->get("waf.mongo.db"));
		foreach ($data as $key => $value) {
			$key = str_replace("-", "_", $key);
			$m = $this->mongo->selectCollection($this->config->get("waf.mongo.dbname"), $key);
			$m->batchInsert($value);
		}
		
	}
	
}
