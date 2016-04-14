<?php
namespace Ypf\Log\Filter;

class Stdout extends Filter {

	public function writer($level, $message) {
		echo $message . "\n";
	}
}