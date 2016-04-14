<?php
namespace Ypf\Log\Filter;
use DateTime;

abstract class Filter {

	public function getUri() {
		$uri = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : null;
		return $uri;		
	}

	public function getFileLine() {
		$trace = array_reverse(debug_backtrace(false));
		$fun = $trace[0];
		if(isset($fun['file'])) {
			$fileline =  str_replace("\\", "\\\\", $fun['file']) . "(" . $fun['line'] . ")";
		}
		return $fileline;
	}

	abstract public function writer($level, $message);
	
}