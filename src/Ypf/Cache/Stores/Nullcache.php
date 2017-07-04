<?php
namespace Ypf\Cache\Stores;

use Ypf\Cache\CacheInterface;

class Nullcache implements CacheInterface {
    
	public function set(string $key, $value, int $ttl = -1) {
	}

    public function get(string $key) {
        return false;
    }

    public function delete(string $key) {
        return true;  
    }
}
