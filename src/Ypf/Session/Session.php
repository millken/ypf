<?php

namespace Ypf\Session;

use Ypf\Http\Request;
use Ypf\Http\Response;
use Ypf\Session\Stores\StoreInterface;

class Session {

    protected $request;
    protected $response;
    protected $store;

    protected $sessionData = [];
    protected $started = false;
    protected $destroyed = false;
    protected $cookieTTL = 0;
    protected $dataTTL = 1800;
    protected $cookieName = 'ypf_session';
	protected $cookieOptions =
	[
		'path'     => '/',
		'domain'   => '',
		'secure'   => false,
		'httponly' => false,
	];

	public function __construct(Request $request, Response $response, StoreInterface $store, array $options = []) {
		$this->request = $request;
		$this->response = $response;
		$this->store = $store;
		$this->configure($options);
		//$this->start();
	}   

	protected function configure(array $options) {
		if(!empty($options)) {
			$this->dataTTL = $options['data_ttl'] ?? $this->dataTTL;
			$this->cookieTTL = $options['cookie_ttl'] ?? $this->cookieTTL;
			$this->cookieName = $options['name'] ?? $this->cookieName;
			isset($options['cookie_options']) && $this->cookieOptions = $options['cookie_options'] + $this->cookieOptions;
		}
	}

    protected function generateId(): string	{
		return hash('sha256', random_bytes(16));
	}

    protected function setCookie() {
		$ttl = $this->cookieTTL === 0 ? 0 : $this->cookieTTL + time();
		$this->response->cookie($this->cookieName, $this->sessionId, $ttl, $this->cookieOptions['path'],
         $this->cookieOptions['domain'], $this->cookieOptions['secure'], $this->cookieOptions['httponly']);
	}

    protected function loadData() {
		$data = $this->store->read($this->sessionId);
		$this->sessionData = $data === false ? [] : $data;
	}

	public function start() {
		$this->started = true;
        
		$this->sessionId = $this->request->cookie($this->cookieName);
		if($this->sessionId === false) {
			$this->sessionId = $this->generateId();
		}
        
		$this->setCookie();
		
		$this->loadData();
	} 

	public function clear()	{
		$this->sessionData = [];
	}

	public function getId(): string	{
		return $this->sessionId;
	}

    public function getData(): array {
		return $this->sessionData;
	}

	public function put(string $key, $value)
	{
		$this->sessionData[$key] = $value;
	}

	public function has(string $key): bool {
		return isset($this->sessionData[$key]);
	}

	public function get(string $key, $default = null) {
		return $this->sessionData[$key] ?? $default;
	}

	public function remove(string $key)	{
		unset($this->sessionData[$key]);
	}

	public function destroy() {
		$this->store->delete($this->sessionId);
		$this->response->cookie($this->cookieName, '', -3600);
		$this->destroyed = true;
	}

	public function save() {
		if($this->started && !$this->destroyed) {
			$this->store->write($this->sessionId, $this->sessionData, $this->dataTTL);
		}
		// Garbage collection
		if(mt_rand(1, 100) === 100)	{
			$this->store->gc($this->dataTTL);
		}
	}    
}