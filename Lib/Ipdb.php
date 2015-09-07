<?php 
namespace Lib;
class Ipdb {

	private static $head = null;
	private static $datacenter = null;
	private static $dbbody = null;
		
	public function load($dbfile = 'ipdb.dat') {
		if(!is_file($dbfile)) {
			throw new \Exception("dbfile : $dbfile  not exists!");
		}
		self::$dbbody = file_get_contents($dbfile);
		self::initIpdbStruct();
	}
	
	protected function initIpdbStruct() {
		$head = substr(self::$dbbody, 0, 10);
		self::$head = unpack("Nver/Nrec_len/ndc_len", $head);
		$datacenter = substr(self::$dbbody, 10 + self::$head['rec_len'] * 7, self::$head['dc_len']);
		for($i = 0; $i < self::$head['dc_len']; $i++) {
			$dc_pack = substr(self::$dbbody, 10 + (self::$head['rec_len'] * 7 ) + $i * 34, 34);
			$dc = unpack("nid/a32name", $dc_pack);
			self::$datacenter[$dc['id']] = $dc['name'];
		}
	}
	
	public function find($ip) {
		if(is_null(self::$head)) {
			$this->load();
		}
		if (empty($ip) === TRUE) {
			return 'N/A';
		}

		$nip   = gethostbyname($ip);
		$longip = ip2long($nip);

		if($longip == false) {
			return 'N/A';
		}	
		$f = 0;
		$l = self::$head['rec_len'] - 1;
		$n = 0;
		//binary chop
		while($f <= $l)
		{
			$m = intval(($f + $l) / 2);
			if(($f +1) == $m) break;
			++ $n;
			$rec_pack = substr(self::$dbbody, 10 + $m * 7, 7);
			$record = unpack("Nip/Cmask/nid", $rec_pack);

			$start =  $record['ip'];
			$end = $start + pow(2 , ( 32 - $record['mask'])) - 1;

			if ( $longip > $end)$f = $m + 1; //move right
			if ( $longip < $start)$l = $m - 1; //move left
			if ($longip >= $start && $longip <= $end) {
					return self::$datacenter[$record['id']];
			}
		}	
	}
	
	public function lookup($ip) {
		$loc = $this->find($ip);
		$ret = array();
		if($loc !== 'N/A') {
			list($ret['country'], $ret['city'], $ret['isp']) = explode("\t", $loc, 3);
		}
		return $ret;
	}
	
	public function get_datacenters() {
		if(is_null(self::$head)) {
			$this->load();
		}
		return self::$datacenter;
	}
	
	public function get_datacenter($id) {
		$cidrs = array();
		if(is_null(self::$head)) {
			$this->load();
		}

		if(!isset(self::$datacenter[$id])) {
			return $cidrs;
		}
		for($i = 0; $i < self::$head['rec_len']; $i++) {
			$rec_pack = substr(self::$dbbody, 10 + $i * 7, 7);
			$record = unpack("Nip/Cmask/nid", $rec_pack);
			if($record['id'] == $id) {
				$ip1 = long2ip($record['ip']);
				$cidrs[] = "{$ip1}/{$record['mask']}";
			}
		}
		return $cidrs;
	}
}
/*
$test = new Ipdb();
$test->load();
echo $test->find('1.0.38.0');
print_r($test->lookup('www.qq.com'));

print_r($test->get_datacenters());
print_r($test->get_datacenter(152));
*/
?>
