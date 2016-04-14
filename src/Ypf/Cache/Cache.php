<?php
namespace Ypf\Cache;
interface Cache {

	function set($key, $value, $expire = 0);

	function get($key);

	function delete($key);
}
