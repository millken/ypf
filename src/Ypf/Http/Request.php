<?php

namespace Ypf\Http;

final class Request {
	public $get = [];
	public $post = [];
	public $cookie = [];
	public $files = [];
	public $server = [];
	public $header = [];

	public function __construct() {
		$this->get = $this->clean($_GET);
		$this->post = $this->clean($_POST);
		$this->request = $this->clean($_REQUEST);
		$this->cookie = $this->clean($_COOKIE);
		$this->files = $this->clean($_FILES);
		$this->server = $this->clean($_SERVER);		
	}

	//swoole can't support multipart form data
	public function init($request) {
		if($request instanceof \swoole_http_request) {
			$this->server = isset($request->server) ? array_change_key_case($request->server, CASE_UPPER) : [];
			$this->header = isset($request->header) ? $request->header : [];
			$this->get = isset($request->get) ? $request->get : [];
			$this->post = isset($request->post) ? $request->post : [];
			$this->cookie = isset($request->cookie) ? $request->cookie : [];
			$this->files = isset($request->files) ? $request->files : [];
		}
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

	public function isPost() {
		return isset($this->server['REQUEST_METHOD']) && strtolower($this->server['REQUEST_METHOD']) == 'post';
	}

	public function cookie($name) {
		return $this->cookie[$name] ?? false;
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
