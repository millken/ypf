<?php

namespace Ypf\Database;

class Pdo {
	static $option = [];
	protected $options = [];
	protected $params = [];
	protected $lastsql = "";
	protected $dsn, $pdo;
	protected $methods = array('from', 'data', 'field', 'table', 'order', 'alias', 'having', 'group', 'lock', 'distinct', 'auto');
	public function __construct($options = []) {
		if (!is_array($options)) {
			throw new \InvalidArgumentException('options must be array');
		}
		$default_options = array(
			'dbtype' => 'mysql',
			'host' => '127.0.0.1',
			'port' => 3306,
			'dbname' => 'test',
			'username' => 'root',
			'password' => '',
			'charset' => 'utf8',
			'timeout' => 3,
			'presistent' => false,
		);
		self::$option = array_merge($default_options, $options);
		$this->dsn = $this->createdsn(self::$option);
	}

	private function createdsn($options) {
		$dsn = $options['dbtype'] . ':host=' . $options['host'] . ';dbname=' . $options['dbname'] . ';port=' . $options['port'];
		switch($options['dbtype']) {
			case 'pgsql':
				$dns .= ';options=--client_encoding=\'' . $options['charset'] . '\'';
		}
		return $dsn;
	}

	public function query($query, $data = []) {
		$this->lastsql = $this->setLastSql($query, $data);
		if (PHP_SAPI == 'cli') {
			try {
				@$this->connection()->getAttribute(\PDO::ATTR_SERVER_INFO);
			} catch (\PDOException $e) {
				switch(self::$option['dbtype']) {
					case 'mysql':
						if ($e->getCode() != 'HY000' || !stristr($e->getMessage(), 'server has gone away')) {
							throw $e;
						}
					break;
				}

				$this->reconnect();
			}
		}
		try {
			$stmt = $this->connection()->prepare($query);
			$stmt->execute($data);
		} catch (\PDOException $e) {
			throw new \Exception("Failed to execute query:\n" . $query . "\nUsing Parameters:\n" . print_r($data, true) . "\nWith Error:\n" . $e->getMessage());
		}
		$this->options = $this->params = [];
		return $stmt;
	}

	public function insert($data = []) {
		$this->options['type'] = 'INSERT';
		return $this->save($data);
	}

	public function select($sql = null, $data = []) {
		$sql = $sql ? $sql : $this->getQuery();
		$data = empty($data) ? $this->params : $data;
		$stmt = $this->query($sql, $data);

		$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		return $result;
	}

	private function setLastSql($string, $data) {
		$indexed = $data == array_values($data);
		foreach ($data as $k => $v) {
			if (is_string($v)) {
				$v = "'$v'";
			}

			if ($indexed) {
				$string = preg_replace('/\?/', $v, $string, 1);
			} else {
				$string = str_replace(":$k", $v, $string);
			}

		}
		return $string;
	}

	public function connect() {
		try {
			$option =  self::$option['dbtype'] == "mysql" && self::$option['charset'] ?
			 array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES ' . self::$option['charset']) : [];
			$option[\PDO::ATTR_TIMEOUT] = self::$option['timeout'];
			$option[\PDO::ATTR_PERSISTENT] = self::$option['presistent'];
			$this->pdo = new \PDO(
				$this->dsn,
				self::$option['username'],
				self::$option['password'],
				$option
			);
			$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		} catch (Exception $e) {
			throw new Exception($e);
		}
		return $this->pdo;
	}
	public function reconnect() {
		$this->pdo = null;
		return $this->connect();
	}

	protected function connection() {
		return $this->pdo instanceof \PDO ? $this->pdo : $this->connect();
	}

	public function getLastSql() {
		return $this->lastsql;
	}

	public function fetch($sql = null) {
		$sql = $sql ? $sql : $this->getQuery();

		$stmt = $this->query($sql, $this->params);
		$result = $stmt->fetch(\PDO::FETCH_ASSOC);
		return $result;
	}

	public function update($data = []) {
		$this->options['type'] = 'UPDATE';
		return $this->save($data);
	}

	public function delete() {
		$this->options['type'] = 'DELETE';
		return $this->save();
	}

	public function fetchOne($sql = null) {
		$this->options['limit'] = 1;
		$sql = $sql ? $sql : $this->getQuery();

		$stmt = $this->query($sql, $this->params);
		$result = $stmt->fetch(\PDO::FETCH_NUM);

		if (isset($result[0])) {
			return $result[0];
		}

		return null;
	}

	public function lastInsertId($name = 'id') {
		$id = false;
		switch(self::$option['dbtype']) {
			case 'pgsql':
				$id = $this->pdo->lastInsertId($name);
			break;
			default:	
				$id = $this->pdo->lastInsertId();
			break;
		}
		return $id;
	}

	public function save($data = []) {
		if (!empty($data)) {
			$this->data($data);
		}

		if (!isset($this->options['type'])) {
			$this->options['type'] = isset($this->options['where']) ? 'UPDATE' : 'INSERT';
		}
        $identifier = self::$option['dbtype'] == "mysql" ? "`" : "\"" ;

		switch ($this->options['type']) {
		case 'INSERT':
			$keys = array_keys($this->options['data']);
			$fields = $identifier . implode("$identifier, $identifier", $keys) . $identifier;
			$placeholder = substr(str_repeat('?,', count($keys)), 0, -1);
			$query = "INSERT INTO $identifier" . $this->options['table'] . "$identifier($fields) VALUES($placeholder)";

			$this->query($query, array_values($data));
			break;
		case 'UPDATE':
			$update_field = [];
			$this->params = array_merge(array_values($this->options['data']), $this->params);
			foreach ($this->options['data'] as $key => $value) {
				$update_field[] = "{$identifier}$key{$identifier}= ?";
			}
			$query = "UPDATE {$identifier}" . $this->options['table'] . "{$identifier} SET " . implode(",", $update_field) . " WHERE " . implode(" AND ", $this->options['where']);
			$this->query($query, $this->params);
			break;
		case 'DELETE':
			$query = "DELETE FROM {$identifier}" . $this->options['table'] . "{$identifier} WHERE " . implode(" AND ", $this->options['where']);
			$this->query($query, $this->params);
			break;
		default:
			# code...
			break;
		}
		return true;
	}

	private function getQuery() {
		$sql = "SELECT ";
		//parse field
		if (isset($this->options['field'])) {
			$sql .= " " . $this->options['field'] . " ";
		} else {
			$sql .= " * ";
		}
		//parse table
		if (isset($this->options['table'])) {
			$sql .= " FROM " . $this->options['table'] . " ";
		}
		//parse join
		if (isset($this->options['join'])) {
			$sql .= $this->options['join'] . " ";
		}
		//parse where
		if (isset($this->options['where'])) {
			$sql .= "WHERE " . implode(" AND ", $this->options['where']) . " ";
		}
		//parse group
		if (isset($this->options['group'])) {
			$sql .= "GROUP BY " . $this->options['group'] . " ";
		}
		//parse having
		if (isset($this->options['having'])) {
			$sql .= "HAVING " . $this->options['having'] . " ";
		}
		//parse order
		if (isset($this->options['order'])) {
			$sql .= "ORDER BY " . $this->options['order'] . " ";
		}
		//parse limit
		if (isset($this->options['limit'])) {
			$sql .= "LIMIT " . $this->options['limit'];
		}
		return $sql;
	}

	public function __call($method, $args) {
		if (in_array(strtolower($method), $this->methods, true)) {
			$this->options[strtolower($method)] = $args[0];
			return $this;
		} elseif (in_array(strtolower($method), array('count', 'sum', 'min', 'max', 'avg'), true)) {
			$field = (isset($args[0]) && !empty($args[0])) ? $args[0] : '*';
			$as = '_' . strtolower($method);
			$this->options['field'] = strtoupper($method) . '(' . $field . ') AS ' . $as;
			return $this->fetchOne();
		} else {
			return null;
		}
	}

	public function addParams($params) {
		if (is_null($params)) {
			return;
		}

		if (!is_array($params)) {
			$params = array($params);
		}

		$this->params = array_merge($this->params, $params);
	}

	/**
	 * Add statement for where - ... WHERE [?] ...
	 *
	 * Examples:
	 * $sql->where(array('uid'=>3, 'pid'=>2));
	 * $sql->where("user_id = ?", $user_id);
	 * $sql->where("u.registered > ? AND (u.is_active = ? OR u.column IS NOT NULL)", array($registered, 1));
	 *
	 * @param string $statement
	 * @param mixed $params
	 * @return Query
	 */
	public function where() {
		$args = func_get_args();
		$statement = $params = null;
		$query_w = [];

		if (func_num_args() == 1 && is_array($args[0])) {
			foreach ($args[0] as $k => $v) {
				$query_w[] = "`$k` = ?";
			}
			$statement = implode(" AND ", $query_w);
			$params = array_values($args[0]);
		} else {
			$statement = array_shift($args);

			$params = isset($args[0]) && is_array($args[0]) ? $args[0] : $args;
		}
		if (!empty($statement)) {
			$this->options['where'][] = $statement;
			$this->addParams($params);
		}
		return $this;
	}

	public function limit($offset, $length = null) {
		$this->options['limit'] = is_null($length) ? $offset : $offset . ',' . $length;
		return $this;
	}

}

?>
