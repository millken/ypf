<?php

namespace Ypf\Core;

abstract class View {
	private $template_dir = [];
	private $data = [];
	static $cache = [];
	private $output;

	public function __construct() {
		if (!ini_get('allow_url_include')) {
			ini_set('allow_url_include', '1');
		}
	}

	/**
	 * $name string|array, otherwise exception error
	 */
	public function assign($name, $value=null) {
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
			if (defined('SWOOLE_SERVER') && !isset(self::$cache[$template_file])) {
				if (!is_file($template_file)) {
					trigger_error('Error: Could not load template ' . $template_file . '!');
				} else {
					self::$cache[$template_file] = base64_encode(file_get_contents($template_file));
				}
			}

			extract($this->data);
			ob_start();
			(defined('SWOOLE_SERVER') && 
			include "data://text/plain;base64," . self::$cache[$template_file]) ||
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
		$this->template_dir = [];
		foreach ((array) $template_dir as $k => $v) {
			$this->template_dir[$k] = preg_replace('#(\w+)(/|\\\\){1,}#', '$1$2', rtrim($v, '/\\')) . DIRECTORY_SEPARATOR;
		}
	}
}
