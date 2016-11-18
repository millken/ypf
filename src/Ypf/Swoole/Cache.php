<?php
namespace Ypf\Swoole;

class Cache {
	private $table;

	public function __construct($size = 1024 * 10) {
		$this->table = new \swoole_table($size);
		$this->table->column('id', \swoole_table::TYPE_INT, 4); //1,2,4,8
		$this->table->column('value', \swoole_table::TYPE_STRING, 1024 * 64);
		$this->table->column('num', \swoole_table::TYPE_FLOAT);
		$this->table->create();
	}

	//https://gist.github.com/cs278/217091
	private function is_serialized($value, &$result = null) {
		// Bit of a give away this one
		if (!is_string($value)) {
			return false;
		}
		// Serialized false, return true. unserialize() returns false on an
		// invalid string or it could return false if the string is serialized
		// false, eliminate that possibility.
		if ($value === 'b:0;') {
			$result = false;
			return true;
		}
		$length = strlen($value);
		$end = '';
		switch ($value[0]) {
		case 's':
			if ($value[$length - 2] !== '"') {
				return false;
			}
		case 'b':
		case 'i':
		case 'd':
			// This looks odd but it is quicker than isset()ing
			$end .= ';';
		case 'a':
		case 'O':
			$end .= '}';
			if ($value[1] !== ':') {
				return false;
			}
			switch ($value[2]) {
			case 0:
			case 1:
			case 2:
			case 3:
			case 4:
			case 5:
			case 6:
			case 7:
			case 8:
			case 9:
				break;
			default:
				return false;
			}
		case 'N':
			$end .= ';';
			if ($value[$length - 1] !== $end[0]) {
				return false;
			}
			break;
		default:
			return false;
		}
		if (($result = @unserialize($value)) === false) {
			$result = null;
			return false;
		}
		return true;
	}

	public function set($key, $value) {
		if (!is_string($value)) {
			$value = serialize($value);
		}

		$this->table->set($key, ['value' => $value]);
	}

	public function get($key) {
		$result = $this->table->get($key);
		$value = $result ? $result['value'] : false;
		$is_serialize = $this->is_serialized($value, $result);
		return $is_serialize ? $result : $value;

	}

	public function del($key) {
		$result = $this->table->del($key);
		return $result;
	}
}
