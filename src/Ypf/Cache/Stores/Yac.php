<?php
namespace Ypf\Cache\Stores;

use Ypf\Cache\CacheInterface;

class Yac implements CacheInterface {

	protected $store;

	public function __construct($prefix='') {
		if (!extension_loaded("yac")) {
			die("Module Yac is not compiled into PHP, See https://github.com/laruence/yac\n");
		}

		if(ini_get("yac.enable_cli") === false && !ini_set("yac.enable_cli", 1)) {
			die("set yac.enable_cli=1 in php.ini\n");
		}

		$this->store = new \Yac($prefix);
	}

	public function set(string $key, $value, int $ttl = -1) {
		if($ttl == -1) {
			$this->store->set($this->key($key), $value);	
		}else{
			$this->store->set($this->key($key), $value, $ttl);
		}
	}

	public function get(string $key) {
		return $this->store->get($this->key($key));
	}

	public function delete(string $key) {
		$this->store->delete($this->key($key));
	}

	private function key($key) {
		return sprintf("%u", crc32($key));
	}

}
