<?php

namespace Ypf\Lib;

class Request {
	public $get = array();
	public $post = array();
	public $cookie = array();
	public $files = array();
	public $server = array();

	public function __construct() {
		$this->get = $this->clean($_GET);
		$this->post = $this->clean($_POST);
		$this->request = $this->clean($_REQUEST);
		$this->cookie = $this->clean($_COOKIE);
		$this->files = $this->clean($_FILES);
		$this->server = $this->clean($_SERVER);
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
		return strtolower($this->server['REQUEST_METHOD']) == 'post';
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
