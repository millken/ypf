<?php
namespace Ypf\Lib;
/**
 *
 * 配置
 *
 */
class Config {
	/**
	 * 配置数据
	 * @var array
	 */
	public static $config = array();

	public static $path = array();

	protected static $instances = null;

	/**
	 * 构造函数
	 * @param empty|string|array $config
	 */
	public function __construct() {
		$args = func_get_args();
		if (!empty($args)) {
			foreach ($args as $path) {
				$this->load($path);
			}
		}
		self::$instances = &$this;
	}

	public function load($path) {
		if (is_file($path)) {
			return self::parseFile($path);
		}

		foreach (glob($path . '/*.conf') as $config_file) {
			self::$path[] = $path;
			$name = basename($config_file, '.conf');
			self::$config[$name] = self::parseFile($config_file);
		}
	}
	/**
	 * 解析配置文件
	 * @param string $config_file
	 * @throws \Exception
	 */
	protected static function parseFile($config_file) {
		$config = parse_ini_file($config_file, true);
		if (!is_array($config) || empty($config)) {
			throw new \Exception('Invalid configuration format');
		}
		return $config;
	}

	public static function getInstance() {
		return self::$instances;
	}

	public static function set($uri, $data) {
		$levels = explode('.', $uri);

		$pointer = &self::$config;
		for ($i = 0; $i < sizeof($levels); $i++) {
			if (!isset($pointer[$levels[$i]])) {
				$pointer[$levels[$i]] = array();
			}

			$pointer = &$pointer[$levels[$i]];
		}

		$pointer = $data;
	}

	/**
	 * 获取配置
	 * @param string $uri
	 * @return mixed
	 */
	public static function get($uri) {
		$node = self::$config;
		$paths = explode('.', $uri);
		while (!empty($paths)) {
			$path = array_shift($paths);
			if (!isset($node[$path])) {
				return null;
			}
			$node = $node[$path];
		}
		return $node;
	}

	/**
	 * 获取所有的workers
	 * @return array
	 */
	public static function getAll() {
		$copy = self::$config;
		return $copy;
	}

}
