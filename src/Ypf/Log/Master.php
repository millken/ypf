<?php
namespace Ypf\Log;

class Master {
	protected static $filters = array();

	protected static $defaultLayout = "%m\n";
	protected static $regex = "/%(?P<word>[a-zA-Z]+)(?P<option>{[^}]*})?/";
	public function addFilter($logLevels, $filter, $layout = null) {
		if (is_object($filter) && is_subclass_of($filter, "\\Ypf\\Log\\Filter\\Filter")) {
			self::$filters[] = array(
				'levels' => $logLevels,
				'filter' => $filter,
				'layout' => $this->parseLayout($layout),
			);
		} else {
			throw new Exception("Supplied parameter is not a object that extends filter");
		}
	}

	/*
		 * %d{pattern} date/time
		 * %f   file
		 * %l   line
		 * %m message
		 * %l   level
	*/
	protected static function parseLayout($layout) {
		$layout = is_null($layout) ? self::$defaultLayout : $layout;
		$count = preg_match_all(self::$regex, $layout, $matches, PREG_OFFSET_CAPTURE);
		if ($count === false) {
			$error = error_get_last();
			throw new Exception("Failed parsing layotut pattern: {$error['message']}");
		}
		$layout_format = $layout;
		$date_format = "";
		$map = [];
		foreach ($matches[0] as $key => $item) {
			$word = !empty($matches['word'][$key]) ? $matches['word'][$key][0] : null;
			$map[] = $word;
			if ($word == 'd') {
				$option = !empty($matches['option'][$key]) ? $matches['option'][$key][0] : null;
				$layout_format = str_replace($option, "", $layout_format);
				$date_format = trim($option, "{} ");
			}
		}
		return [
			'date_format' => $date_format,
			'layout_format' => $layout_format,
			'map' => $map,
		];
	}

	public static function prepare($args) {
		$msg = $args[0];

		if (is_object($msg) && is_subclass_of($msg, "\\Exception")) {
			$out = $msg->getMessage();
			foreach ($msg->getTrace() as $fun) {
				if (isset($fun['file'])) {
					$out .= "\tat " . $fun['function'] . " in " . $fun['file'] . "(" . $fun['line'] . ")\n";
				} else {
					$out .= "\tat " . $fun['function'] . " in ??? (???)\n";
				}
			}
			return $out;
		} else {
			for ($i = 1; $i < count($args); $i++) {
				$repl = "";
				if (is_object($args[$i]) && method_exists($args[$i], "__toString")) {
					$repl = $args[$i]->__toString();
				} else if (is_object($args[$i]) || is_array($args[$i])) {
					$repl = "{ " . str_replace("\n", ",", print_r($args[$i], true)) . " }";
				} else {
					$repl = $args[$i];
				}
				$msg = preg_replace("/\\{\\}/", $repl, $msg, 1);
			}
			if (is_array($msg)) {
				$msg = print_r($msg, true);
			}

			return $msg;
		}
	}

	public function __call($level, $value) {
		array_walk(self::$filters, function ($filters) use ($value, $level) {
			$catch = false;
			if (is_string($filters['levels']) && ($filters['levels'] == Level::ALL
				|| $filters['levels'] == $level)) {
				$catch = true;
			} elseif (is_array($filters['levels']) && in_array($level, $filters['levels'])) {
				$catch = true;
			}

			if ($catch) {
				$message = Master::prepare($value);
				$file = $line = null;
				if (in_array('f', $filters['layout']['map']) or in_array('l', $filters['layout']['map'])) {
					$trace = debug_backtrace(false);
					$fun = $trace[2];
					if (isset($fun['file'])) {
						$file = str_replace("\\", "\\\\", $fun['file']);
						$line = $fun['line'];
					}
				}

				$uri = isset($_SERVER["REQUEST_URI"]) ? $_SERVER["REQUEST_URI"] : null;
				$message = str_replace(["%d", "%f", "%l", "%p", '%uri', "%m"],
					[date($filters['layout']['date_format']), $file, $line, $level, $uri, $message],
					$filters['layout']['layout_format']);
				$filters['filter']->writer($level, $message);
			}
		});
	}

}
