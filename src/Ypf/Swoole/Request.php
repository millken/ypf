<?php

namespace Ypf\Swoole;

final class Request {
	public $get = [];
	public $post = [];
	public $cookie = [];
	public $files = [];
	public $server = [];
	public $header = [];

	public function __construct() {
	}

	public function init(\swoole_http_request $request) {
		$this->server = isset($request->server) ? array_change_key_case($request->server, CASE_UPPER) : [];
		$this->header = isset($request->header) ? $request->header : [];
		$this->get = isset($request->get) ? $request->get : [];
		$this->post = isset($request->post) ? $request->post : [];
		$this->cookie = isset($request->cookie) ? $request->cookie : [];
		$this->files = isset($request->files) ? $request->files : [];
	}

	public function isPost() {
		return isset($this->server['REQUEST_METHOD']) && strtolower($this->server['REQUEST_METHOD']) == 'post';
	}

	public function get($name, $filter = null, $default = null) {
		$value = $default;
		if (isset($this->get[$name])) {
			if (!is_null($filter) && function_exists($filter)) {
				$value = $filter($this->get[$name]);
			} else {
				$value = $this->get[$name];
			}
		}
		return $value;
	}

	public function post($name, $filter = null, $default = null) {
		$value = $default;
		if (isset($this->post[$name])) {
			if (!is_null($filter) && function_exists($filter)) {
				$value = $filter($this->post[$name]);
			} else {
				$value = $this->post[$name];
			}
		}
		return $value;
	}

}
