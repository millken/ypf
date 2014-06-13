<?php

namespace Ypf\Lib;

class Log {
	private $filehandle;
	private $record_level = 0;
	private static $levels = array(
			'INFO', 'WARN', 'DEBEG', 'ERROR'
		);

	public function __construct($filename) {
		$file = $filename;
		$this->filehandle = fopen($file, 'a');
	}

	public function __destruct() {
		fclose($this->filehandle);
	}

	public function SetLevel($level) {
		$this->record_level = $level;
	}

	private function write($level, $message) {
		if ($level >= $this->record_level)
		fwrite($this->filehandle, date('Y-m-d G:i:s') . '- [' . self::$levels[$level] . '] - ' . print_r($message, true) . "\n");
	}

	public function Error($message) {
		$this->write(3, $message);
	}

	public function Debug($message) {
		$this->write(2, $message);
	}

	public function Warn($message) {
		$this->write(1, $message);
	}

	public function Info($message) {
		$this->write(0, $message);
	}	

}