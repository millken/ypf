<?php
namespace Ypf\Cache;

interface CacheInterface {

	function set(string $key, $value, int $ttl = -1);

	function get(string $key);

	function delete(string $key);
}
