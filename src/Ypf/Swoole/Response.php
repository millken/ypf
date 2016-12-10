<?php

namespace Ypf\Swoole;

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

    public static function getFileExt($file) {
        $s = strrchr($file, '.');
        if ($s === false)
        {
            return false;
        }
        return strtolower(trim(substr($s, 1)));
    }
	public function sendfile($file) {
		$this->response->header("Content-Type", self::$mimes[self::getFileExt($file)]);
		$this->response->sendfile($file);
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
