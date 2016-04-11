<?php

namespace Ypf\Core;

abstract class View {
	private $template_dir = array();
	private $data = array();
	static $cache = array();
	private $output;

	/**
	 * $name string|array, otherwise exception error
	 */
	public function assign($name, $value) {
		if (is_array($name)) {
			foreach ((array) $name as $_k => $_v) {
				$this->data[$_k] = $_v;
			}
		} elseif (is_string($name)) {
			$this->data[$name] = $value;
		} else {
			throw new \Exception("\$name only accept string or array");
		}
	}

	/*
		 * $template string
	*/
	public function fetch($template, $display = false) {
		foreach ($this->template_dir as $key => $dir) {
			$template_file = $dir . $template;
			if (!isset(self::$cache[$template_file])) {
				if (!is_file($template_file)) {
					trigger_error('Error: Could not load template ' . $template_file . '!');
				} else {
					self::$cache[$template_file] = file_get_contents($template_file);
				}
			}

			extract($this->data);
			ob_start();
			eval("?>" . self::$cache[$template_file] . "<?php ");
			$this->output = ob_get_contents();
			ob_end_clean();
			if ($display) {
				echo $this->output;
			} else {
				return $this->output;
			}

		}
	}

	/*
		 * $template string
	*/
	public function display($template) {
		$this->fetch($template, true);
	}

	/*
		 * $template string|array
	*/
	public function setTemplateDir($template_dir) {
		$this->template_dir = array();
		foreach ((array) $template_dir as $k => $v) {
			$this->template_dir[$k] = preg_replace('#(\w+)(/|\\\\){1,}#', '$1$2', rtrim($v, '/\\')) . DIRECTORY_SEPARATOR;
		}
	}
}
