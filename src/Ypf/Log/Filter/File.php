<?php
namespace Ypf\Log\Filter;

class File extends Filter {
	private $filename;

	public function writer($level, $message) {
		file_put_contents($this->filename, $message . "\n", FILE_APPEND);
	}

	public function setFile($filename) {
		$this->filename = $filename;
	}

}