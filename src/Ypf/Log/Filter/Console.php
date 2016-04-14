<?php
namespace Ypf\Log\Filter;
use DateTime;

class Console extends Filter {

	public function writer($level, $message) {
		$t = microtime(true);
		$micro = sprintf("%06d",($t - floor($t)) * 1000000);
		$d = new DateTime(date('Y-m-d H:i:s.') . $micro);
		$message = $d->format("Y-m-d H:i:s.u") . " ][ " . strtoupper($level) . " ][ " . $this->getFileLine(). " ][ "
				. $this->getUri() . " ]: " . $message;

		echo "<script>console.log('" . str_replace("\n", "\\n", $message) . "');</script>";
	}
}