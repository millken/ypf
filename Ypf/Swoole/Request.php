<?php

namespace Ypf\Swoole;

class Request {
	public $get = array();
	public $post = array();
	public $cookie = array();
	public $files = array();
	public $server = array();
	public $header = array();

	public function __construct() {
	}
	
	public function init(\swoole_http_request $request) {
        $this->server = isset($request->server) ? $this->clean($request->server) : array();
        $this->header = isset($request->header) ? $this->clean($request->header) : array();
        $this->get    = isset($request->get) ? $this->clean($request->get) : array();
        $this->post   = isset($request->post) ? $this->clean($request->post) : array();
        $this->cookie = isset($request->cookie) ? $this->clean($request->cookie) : array();
        $this->files  = isset($request->files) ? $this->clean($request->files) : array();
	}
	
	public function clean($data) {
		if (is_array($data)) {
			foreach ($data as $key => $value) {
				unset($data[$key]);

				$data[$this->clean($key)] = $this->clean($value);
			}
		} else {
			$data = htmlspecialchars($data, ENT_COMPAT, 'UTF-8');
		}

		return $data;
	}
}
