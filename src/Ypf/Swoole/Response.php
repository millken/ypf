<?php

namespace Ypf\Swoole;

final class Response {
	private $headers = array();
	private $level = 0;
	private $output = '';
	protected $response;
	protected static $instances = null;

	public function __construct() {
		self::$instances = &$this;
	}

	public static function &getInstance() {
		return self::$instances;
	}

	public function addHeader($header_key, $header_value) {
		$this->headers[] = [$header_key, $header_value];
	}

	public function init(\swoole_http_response $response) {
		$this->response = $response;
	}

	public function redirect($url, $status = 302) {
		$this->response->status($status);
		$this->response->header("Location", str_replace(array('&amp;', "\n", "\r"), array('&', '', ''), $url));
		$this->response->end();
	}

	public function setCompression($level) {
		$this->level = $level;
	}

	public function setOutput($output) {
		$this->output = $output;
	}

	public function getOutput() {
		return $this->output;
	}

	public function output() {
		if ($this->output) {
			if ($this->level) {
				$this->response->gzip($this->level);
			}

			foreach ($this->headers as $header) {
				$this->response->header($header[0], $header[1]);
			}
			$this->response->end($this->output);
		}
		$this->headers = [];
		$this->output = '';
		$this->level = 0;
	}
}
