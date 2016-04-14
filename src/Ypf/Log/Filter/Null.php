<?php
namespace Ypf\Log\Filter;

class Null extends Filter {

	public function writer($level, $message) {
	}
}