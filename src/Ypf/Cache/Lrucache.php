<?php
namespace Ypf\Cache;

use Ypf;

class Lrucache implements Ypf\Cache\Cache {
	protected $cache = [];
	protected $size = 0;

	function __construct($size) {
		if (!is_int($size) || (int) $size <= 0) {
			throw new \InvalidArgumentException('Size must be positive integer');
		}
		$this->size = (int) $size;
		$this->data = [];
	}

	function get($key) {
		if (!array_key_exists($key, $this->data) or !isset($this->data[$key]['value'])) {
			return false;
		}

		if ($this->isExpire($key)) {
			$this->delete($key);
			return false;
		}

		$this->changeKeyToLastUsed($key, $this->data[$key]);
		return $this->data[$key]['value'];
	}

	function delete($key) {
		if (!array_key_exists($key, $this->data)) {
			return false;
		}
		unset($this->data[$key]);
		return true;
	}

	function cache($key, $func, $expire = -1) {
		$value = $this->get($key);
		if ($value === false) {
			$value = $func();
			$this->set($key, $value, $expire);
		}
		return $value;
	}

	function set($key, $value, $expire = -1) {
		if (isset($this->data[$key]) || array_key_exists($key, $this->data)) {
			$this->changeKeyToLastUsed($key, $value);
			return;
		}
		if ($this->isLimitReached()) {
			$this->removeEarliestUsedKey();
		}
		$this->data[$key] = [
			'value' => $value,
			'expire' => ($expire > 0 ? time() + $expire : $expire),
		];
	}

	protected function removeEarliestUsedKey() {
		array_shift($this->data);
	}

	protected function isLimitReached() {
		return count($this->data) >= $this->size;
	}

	protected function isExpire($key) {
		if (!isset($this->data[$key]) or !isset($this->data[$key]['expire'])) {
			return false;
		}
		$expire = $this->data[$key]['expire'];
		if ($expire < 0) {
			return false;
		} elseif (time() >= $expire) {
			return true;
		}
		return false;
	}

	protected function changeKeyToLastUsed($key, $value) {
		unset($this->data[$key]);
		$this->data[$key] = $value;
	}

}
