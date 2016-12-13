<?php
namespace Ypf\Cache\Stores;

use Ypf\Cache\CacheInterface;

class Yac implements CacheInterface {

	protected $store;

	public function __construct($prefix='') {
		if (!extension_loaded("yac")) {
			die("Module Yac is not compiled into PHP");
		}
		$this->store = new Yac($prefix);
	}

	public function set($key, $value, $ttl = -1) {
        $ttl = $ttl == -1 ? 999999999 : $ttl;
		$this->store->set($key, $value, $ttl);
	}

	public function get(string $key) {
		return $this->store->get($key);
	}


	public function delete(string $key) {
		$this->store->delete($key);
	}

}
