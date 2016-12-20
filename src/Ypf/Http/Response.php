<?php

namespace Ypf\Http;

final class Response {
	private $headers = array();
	private $level = 0;
	private $output = '';
	protected $response;
	protected static $instances = null;

	protected static $mimes = array(
		'image/jpeg' => 'jpg',
		'image/bmp' => 'bmp',
		'image/x-icon' => 'ico',
		'image/gif' => 'gif',
		'image/png' => 'png',
		'application/octet-stream' => 'bin',
		'application/javascript' => 'js',
		'text/css' => 'css',
		'text/html' => 'html',
		'text/xml' => 'xml',
		'application/x-tar' => 'tar',
		'application/vnd.ms-powerpoint' => 'ppt',
		'application/pdf' => 'pdf',
		'application/x-shockwave-flash' => 'swf',
		'application/x-zip-compressed' => 'zip',
		'application/gzip' => 'gzip',
		'application/x-woff' => 'woff',
		'image/svg+xml' => 'svg',
	);

	public function __construct() {
		self::$instances = &$this;
		self::$mimes = array_flip(self::$mimes);
	}

	public static function &getInstance() {
		return self::$instances;
	}

	public function addHeader($header_key, $header_value) {
		$this->headers[] = [$header_key, $header_value];
	}

	public function init($response) {
		if($response instanceof \swoole_http_response) {
			$this->response = $response;
		}
	}

	public function redirect($url, $status = 302) {
		$url = str_replace(array('&amp;', "\n", "\r"), array('&', '', ''), $url);
		if(defined("SWOOLE_SERVER")) {
			$this->response->status($status);
			$this->response->header("Location", $url);
			$this->response->end();
		}else{
			header('Status: ' . $status);
			header('Location: ' . $url);
			exit();
		}
	}

	public function cookie(string $key, string $value = '', int $expire = 0 , string $path = '/', string $domain  = '', bool $secure = false , bool $httponly = false) {
		if(defined("SWOOLE_SERVER")) {
			$this->response->cookie($key,  $value, $expire, $path, $domain, $secure, $httponly);
		}else{
			setcookie($key,  $value, $expire, $path, $domain, $secure, $httponly);
		}
	}

	public function setCompression($level) {
		$this->level = $level;
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

		return gzencode($data, (int) $level);
	}
	
	public function setOutput($output) {
		$this->output = $output;
	}

	public function getOutput() {
		return $this->output;
	}

    public static function getFileExt($file) {
        $s = strrchr($file, '.');
        if ($s === false) {
            return false;
        }
        return strtolower(trim(substr($s, 1)));
    }

	public function sendfile($file) {
		$this->response->header("Content-Type", self::$mimes[self::getFileExt($file)]);
		$this->response->sendfile($file);
	}

	private function output_swoole() {

		if ($this->level) {
			$this->response->gzip($this->level);
		}

		foreach ($this->headers as $header) {
			$this->response->header($header[0], $header[1]);
		}
		$this->response->end($this->output);
	
		$this->headers = [];
		$this->output = '';
		$this->level = 0;
	}

	public function output() {
		if(defined("SWOOLE_SERVER")) {
			$this->output_swoole();
		}else
		if ($this->output) {
			if ($this->level) {
				$output = $this->compress($this->output, $this->level);
			} else {
				$output = $this->output;
			}

			if (!headers_sent()) {
				foreach ($this->headers as $header) {
					$header = implode(": ", $header);
					header($header, true);
				}
			}

			echo $output;
		}

	}
}
