<?php

namespace Ypf\Swoole;

class Response {
	private $headers = array();
	private $level = 0;
	private $output;
	private $response;

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

	private function compress($data, $level = 0) {
		if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false)) {
			$encoding = 'gzip';
		}

		if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false)) {
			$encoding = 'x-gzip';
		}

		if (!isset($encoding)) {
			return $data;
		}

		if (!extension_loaded('zlib') || ini_get('zlib.output_compression')) {
			return $data;
		}

		if (headers_sent()) {
			return $data;
		}

		if (connection_status()) {
			return $data;
		}

		$this->addHeader('Content-Encoding: ' . $encoding);

		return gzencode($data, (int)$level);
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
	}
}
