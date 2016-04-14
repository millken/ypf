<?php
namespace Ypf\Log;

class Master {
	protected static $filters = array();

	public function addFilter($logLevels, $filter) {
		if(is_object($filter) && is_subclass_of($filter, "\\Ypf\\Log\\Filter\\Filter")) {
			self::$filters[] = array(
				'levels' => $logLevels,
				'filter' => $filter,
				);
		} else {
			throw new Exception("Supplied parameter is not a object that extends filter");
		}
	}

	public static function prepare($args) {
		$msg = $args[0];

		if(is_object($msg) && is_subclass_of($msg, "\\Exception")) {
			$out = $msg->getMessage();
			foreach($msg->getTrace() as $fun) {
				if(isset($fun['file'])) {
					$out .= "\tat " . $fun['function'] . " in " . $fun['file'] . "(" . $fun['line'] . ")\n";
				} else {
					$out .= "\tat " . $fun['function'] . " in ??? (???)\n";
				}
			}
			return $out;
		} else {
			for($i = 1; $i < count($args); $i++) {
				$repl = "";
				if(is_object($args[$i]) && method_exists($args[$i], "__toString")) {
					$repl = $args[$i]->__toString();
				} else if(is_object($args[$i]) || is_array($args[$i])) {
					$repl = "{ " . str_replace("\n", ",", print_r($args[$i], true)) . " }";
				} else {
					$repl = $args[$i];
				}
				$msg = preg_replace("/\\{\\}/", $repl, $msg, 1);
			}
			if(is_array($msg)) $msg = print_r($msg, true);
			
			return $msg;
		}
	}


	public function __call($level, $value) {
		array_walk(self::$filters, function($filters) use($value, $level) {
			$catch = false;
			if(is_string($filters['levels']) && ($filters['levels'] == Level::ALL
				|| $filters['levels'] == $level)) $catch = true;
			elseif(is_array($filters['levels']) && in_array($level, $filters['levels'])) $catch = true;
			if($catch){
				$message = Master::prepare($value);
				$filters['filter']->writer($level, $message);
			}
		});	
	}

}