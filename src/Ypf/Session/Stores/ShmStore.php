<?php

namespace Ypf\Session\Stores;

class ShmStore implements \Ypf\Session\Stores\StoreInterface {

	protected $store;

	public function __construct() {
		if (function_exists("shm_attach") === FALSE) {
			die("\nYour PHP configuration needs adjustment. To enable the System V shared memory support compile PHP with the option --enable-sysvshm.");
		}
		$shm_id = ftok(__FILE__, 'a');
		$this->store = shm_attach($shm_id, 131989504);
	}

	/**
	 * {@inheritdoc}
	 */
	public function write(string $sessionId, array $sessionData, int $dataTTL)
	{
		$data = [
			'ttl' => $dataTTL,
			'time' => time(),
			'data' => $sessionData,
		];
		shm_put_var($this->store, $this->key($sessionId), serialize($data));
	}

	private function key($key) {
		return (int) sprintf("%u\n", crc32($key));
	}

	private function has($key) {
		if (shm_has_var($this->store, $this->key($key))) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function read(string $sessionId): array
	{
		if ($this->has($sessionId)) {
			$var = shm_get_var($this->store, $this->key($sessionId));
			$data = unserialize($var);

			if($data["time"] + $data["ttl"] <= time()) {
				$this->delete($sessionId);
			}else{
				$this->write($sessionId, $data["data"], $data["ttl"]);
				return $data["data"];
			}
		}
		return [];
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete(string $sessionId)
	{
		if ($this->has($sessionId)) {
			return shm_remove_var($this->store, $this->key($sessionId));
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function gc(int $dataTTL)
	{

	}
}
