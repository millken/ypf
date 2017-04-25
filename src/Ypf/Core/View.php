<?php

namespace Ypf\Core;

abstract class View {
	private $template_dir = [];
	private $base_dir = '';
	private $data = [];
	static $cache = [];
	private $output;

	/**
	 * $name string|array, otherwise exception error
	 */
	public function assign($name, $value = null) {
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

	public function fetch(string $template, bool $display = false) {
		foreach ($this->template_dir as $key => $dir) {

			$template_file = $this->base_dir . $dir . $template;

			extract($this->data);
			ob_start();
			include $template_file;
			$this->output = ob_get_contents();
			$this->data = [];
			ob_end_clean();

			if ($display) {
				echo $this->output;
			} else {
				return $this->output;
			}

		}
	}

	public function display(string $template) {
		$this->fetch($template, true);
	}

	public function setBaseDir($base_dir) {
		$this->base_dir = $base_dir;
	}

	/*
		 * $template string|array
	*/
	public function setTemplateDir($template_dir) {
		$this->template_dir = [];
		foreach ((array) $template_dir as $k => $v) {
			$this->template_dir[$k] = preg_replace('#(\w+)(/|\\\\){1,}#', '$1$2', rtrim($v, '/\\')) . DIRECTORY_SEPARATOR;
		}
	}
}
