<?php
namespace Ypf\Cache;

class Shm {
	private $shm_id;
	private $shm_size;
	public $shm;

	public function __construct($shm_id = 0x701da13b, $shm_size = 33554432) {
		if (function_exists("shm_attach") === FALSE) {
			die("\nYour PHP configuration needs adjustment. See: http://us2.php.net/manual/en/shmop.setup.php. To enable the System V shared memory support compile PHP with the option --enable-sysvshm.");
		}
		$this->shm_id = $shm_id;
		$this->shm_size = $shm_size;
		$this->attach();
	}

	public function attach() {
		$this->shm = shm_attach($this->shm_id, $this->shm_size);
	}

	public function dettach() {
		return shm_detach($this->shm);
	}

	public function remove() {
		return shm_remove($this->shm);
	}

	public function set($key, $var) {
		return shm_put_var($this->shm, $this->shm_key($key), $var);
	}

	public function get($key) {
		if ($this->has($key)) {
			return shm_get_var($this->shm, $this->shm_key($key));
		} else {
			return false;
		}
	}

	public function del($key) {
		if ($this->has($key)) {
			return shm_remove_var($this->shm, $this->shm_key($key));
		} else {
			return false;
		}
	}
	public function has($key) {
		if (shm_has_var($this->shm, $this->shm_key($key))) {
			return true;
		} else {
			return false;
		}
	}

	public function shm_key($key) {
		return (int) sprintf("%u\n", crc32($key));
	}

	public function __wakeup() {
		$this->attach();
	}

	public function __destruct() {
		$this->dettach();
		unset($this);
	}
}
