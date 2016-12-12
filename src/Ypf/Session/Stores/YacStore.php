<?php

namespace Ypf\Session\Stores;

class YacStore implements \Ypf\Session\Stores\StoreInterface {

	protected $store;

	public function __construct() {
		if (!extension_loaded("yac")) {
			die("Module Yac is not compiled into PHP");
		}
		$this->store = new Yac("sess_");
	}
	/**
	 * {@inheritdoc}
	 */
	public function write(string $sessionId, array $sessionData, int $dataTTL)
	{
		$this->store->set($sessionId, $sessionData, $dataTTL);
	}

	/**
	 * {@inheritdoc}
	 */
	public function read(string $sessionId): array
	{
		return $this->store->get($sessionId);
	}

	/**
	 * {@inheritdoc}
	 */
	public function delete(string $sessionId)
	{
		$this->store->delete($sessionId);
	}

	/**
	 * {@inheritdoc}
	 */
	public function gc(int $dataTTL)
	{

	}
}
