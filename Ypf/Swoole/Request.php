<?php

namespace Ypf\Swoole;

class Request {
	public $get = [];
	public $post = [];
	public $cookie = [];
	public $files = [];
	public $server = [];
	public $header = [];

	public function __construct() {
	}
	
	public function init(\swoole_http_request $request) {
        $this->server = isset($request->server) ? $this->clean($request->server) : [];
        $this->header = isset($request->header) ? $this->clean($request->header) : [];
        $this->get    = isset($request->get) ? $this->clean($request->get) : [];
        $this->post   = isset($request->post) ? $this->clean($request->post) : [];
        $this->cookie = isset($request->cookie) ? $this->clean($request->cookie) : [];
        $this->files  = isset($request->files) ? $this->clean($request->files) : [];
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
