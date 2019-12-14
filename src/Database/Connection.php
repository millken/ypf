<?php

namespace Ypf\Database;

use PDO;
use Exception;
use PDOException;
use InvalidArgumentException;

class Connection
{
    public static $option = [];
    protected $sqldata = [];
    protected $lastsql = '';
    protected $dsn;
    protected $pdo;
    protected $commands;
    protected $guid = 0;

    public function __construct($options = [])
    {
        if (!is_array($options)) {
            throw new InvalidArgumentException('options must be array');
        }
        $default_options = [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'test',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8',
            'prefix' => '',
            'timeout' => 3,
            'presistent' => false,
        ];
        static::$option = array_merge($default_options, $options);
        $this->dsn = $options['driver'].':host='.static::$option['host'].';dbname='.static::$option['database'].
        ';port='.static::$option['port'];
        if (isset($options['dsn'])) {
            $this->dsn = $options['dsn'];
        }

        if (isset($options['command']) && is_array($options['command'])) {
            $this->commands = $options['command'];
        } else {
            $this->commands = [];
        }
        if ($options['charset']) {
            $this->commands += ["SET NAMES '".$options['charset']."'"];
        }
    }

    public function query($query, $data = [])
    {
        $this->lastsql = $query;
        $this->sqldata = $data;
        if (PHP_SAPI == 'cli') {
            try {
                @$this->connection()->getAttribute(PDO::ATTR_SERVER_INFO);
            } catch (PDOException $e) {
                $this->reconnect();
            }
        }
        try {
            $stmt = $this->connection()->prepare($query);
            $stmt->execute($data);
        } catch (PDOException $e) {
            throw new Exception('Failed to execute query: '.$query.' Using Parameters: '.print_r($data, true)
            .' With Error: '.$e->getMessage().' in '.$e->getFile().' on line '.$e->getLine());
        }

        return $stmt;
    }

    protected function tableQuote($table)
    {
        if (strpos($table, '.') !== false) {
            return '"'.str_replace('.', '"."', $table).'"';
        }

        return '"'.static::$option['prefix'].$table.'"';
    }

    protected function columnQuote($string)
    {
        if (strpos($string, '.') !== false) {
            if (strpos($string, '*') !== false) {
                return '"'.str_replace('.', '".', $string);
            }

            return '"'.str_replace('.', '"."', $string).'"';
        } elseif (strpos($string, '(') !== false || strpos($string, ' ') !== false) {
            return $string;
        }

        return '"'.$string.'"';
    }

    public function id()
    {
        $id = 0;
        switch (static::$option['driver']) {
            case 'oracle':
            $id = 0;
            break;
            case 'pgsql':
            $id = $this->pdo->query('SELECT LASTVAL()')->fetchColumn();
            break;
            case 'mssql':
            $id = $this->pdo->query('SELECT SCOPE_IDENTITY()')->fetchColumn();
            break;
            default:
            $id = $this->pdo->lastInsertId();
            break;
        }

        return $id;
    }

    public function insert(string $table, array $data)
    {
        $keys = array_keys($data);
        $fields = [];
        foreach ($keys as $key) {
            $fields[] = $this->columnQuote($key);
        }

        $placeholder = substr(str_repeat('?,', count($keys)), 0, -1);
        $query = 'INSERT INTO '.$this->tableQuote($table).'('.implode(',', $fields).") VALUES ($placeholder)";
        $this->query($query, array_values($data));
    }

    public function update(string $table, array $data, array $where)
    {
        $fields = [];
        $map = [];
        foreach ($data as $key => $value) {
            $mapkey = $this->mapKey();
            $fields[] = $this->columnQuote($key).'='.$mapkey;
            $map[$mapkey] = $value;
        }
        $query = 'UPDATE '.$this->tableQuote($table).' SET '
        .implode(',', $fields).$this->whereClause($where, $map);

        $this->query($query, $map);
    }

    public function delete(string $table, array $where)
    {
        $map = [];
        $query = 'DELETE FROM '.$this->tableQuote($table).$this->whereClause($where, $map);

        $this->query($query, $map);
    }

    protected function arrayQuote($array)
    {
        $stack = [];
        foreach ($array as $value) {
            $stack[] = is_int($value) ? $value : $this->pdo->quote($value);
        }

        return implode($stack, ',');
    }

    protected function mapKey()
    {
        if ($this->guid >= PHP_INT_MAX) {
            $this->guid = 0;
        }
        return ':var_'.$this->guid++;
    }

    protected function dataImplode($data, &$map)
    {
        $stack = [];
        foreach ($data as $key => $value) {
            if (is_integer($key)) {
                if (is_array($value)) {
                    if (isset($value['field'])) {
                        $field = $value['field'];
                        $mapkey = $this->mapKey();
                        $operator = $value['operator'] ?? '';
                        $connector = $value['connector'] ?? 'AND';
                        if (isset($value['value'])) {
                            $stack[] = (empty($stack) ? '' : (' '.$connector.' ')).$this->columnQuote($field).' '.$operator.' '.$mapkey;
                            $map[$mapkey] = $value['value'];
                        } else {
                            $stack[] = (empty($stack) ? '' : (' '.$connector.' ')).$this->columnQuote($field).' '.$operator;
                        }
                    } elseif (isset($value['subexpression'])) {
                        $connector = $value['connector'] ?? 'AND';
                        $stack[] = (empty($stack) ? '' : (' '.$connector.' ')).'('.$this->dataImplode($value['subexpression'], $map).')';
                    }
                }
            } else {
                $connector = $value['connector'] ?? 'AND';
                $mapkey = $this->mapKey();
                $stack[] = (empty($stack) ? '' : (' '.$connector.' ')).$this->columnQuote($key).'='.$mapkey;
                $map[$mapkey] = $value;
            }
        }

        return implode(' ', $stack);
    }

    /*
    [
        [
            'field' => 'id',
            'operator' => 'like',
            'value' => '%ab%',
        ],
        [
            'field' => 'number2',
            'alias' => 'n2',
            'operator' => '>',
            'value' => 3,
        ],
        [
            'field' => 'b',
            'operator' => 'is not null',
        ],
        [
            "connector' => 'OR',
            'sub' => [

            ]
        ]
    ]
    */
    protected function whereClause(array $where, &$map)
    {
        $where_clause = '';
        if (is_array($where)) {
            $where_keys = array_keys($where);
            $conditions = array_diff_key($where, array_flip(
                ['GROUP', 'HAVING', 'ORDER', 'LIMIT']
            ));
            if (!empty($conditions)) {
                $where_clause = ' WHERE '.$this->dataImplode($conditions, $map);
            }
            if (isset($where['GROUP'])) {
                $GROUP = $where['GROUP'];
                if (is_array($GROUP)) {
                    $stack = [];
                    foreach ($GROUP as $column => $value) {
                        $stack[] = $this->columnQuote($value);
                    }
                    $where_clause .= ' GROUP BY '.implode($stack, ',');
                } elseif ($raw = $this->buildRaw($GROUP, $map)) {
                    $where_clause .= ' GROUP BY '.$raw;
                } else {
                    $where_clause .= ' GROUP BY '.$this->columnQuote($GROUP);
                }
                if (isset($where['HAVING'])) {
                    if ($raw = $this->buildRaw($where['HAVING'], $map)) {
                        $where_clause .= ' HAVING '.$raw;
                    } else {
                        $where_clause .= ' HAVING '.$this->dataImplode($where['HAVING'], $map, ' AND');
                    }
                }
            }
            if (isset($where['ORDER'])) {
                $ORDER = $where['ORDER'];
                if (is_array($ORDER)) {
                    $stack = [];
                    foreach ($ORDER as $column => $value) {
                        if (is_array($value)) {
                            $stack[] = 'FIELD('.$this->columnQuote($column).', '.$this->arrayQuote($value).')';
                        } elseif ($value === 'ASC' || $value === 'DESC') {
                            $stack[] = $this->columnQuote($column).' '.$value;
                        } elseif (is_int($column)) {
                            $stack[] = $this->columnQuote($value);
                        }
                    }
                    $where_clause .= ' ORDER BY '.implode($stack, ',');
                } else {
                    $where_clause .= ' ORDER BY '.$this->columnQuote($ORDER);
                }
                if (
                    isset($where['LIMIT']) &&
                    in_array(static::$option['driver'], ['oracle', 'mssql'])
                ) {
                    $LIMIT = $where['LIMIT'];
                    if (is_numeric($LIMIT)) {
                        $LIMIT = [0, $LIMIT];
                    }
                    if (
                        is_array($LIMIT) &&
                        is_numeric($LIMIT[0]) &&
                        is_numeric($LIMIT[1])
                    ) {
                        $where_clause .= ' OFFSET '.$LIMIT[0].' ROWS FETCH NEXT '.$LIMIT[1].' ROWS ONLY';
                    }
                }
            }
            if (isset($where['LIMIT'])
            && !in_array(static::$option['driver'], ['oracle', 'mssql'])) {
                $LIMIT = $where['LIMIT'];
                if (is_numeric($LIMIT)) {
                    $where_clause .= ' LIMIT '.$LIMIT;
                } elseif (
                    is_array($LIMIT) &&
                    is_numeric($LIMIT[0]) &&
                    is_numeric($LIMIT[1])
                ) {
                    $where_clause .= ' LIMIT '.$LIMIT[1].' OFFSET '.$LIMIT[0];
                }
            }
        }

        return $where_clause;
    }

    /*
    [
        'table' => ['a', 'b'],
        'join' => "left join",
        'on' => ['a.id', 'b.aid']
    ]
     */
    public function select($table, string $field = '*', array $where = [])
    {
        $join_table = '';
        if (is_array($table)) { //join
            $tables = $table['table'] ?? [];
            $join = $table['join'] ?? '';
            $on = $table['on'] ?? [];
            $table = $tables[0];

            $join_table = " $join ".$this->tableQuote($tables[1]).' ON '.$this->columnQuote($on[0]).'='.$this->columnQuote($on[1]);
        }
        $cloumnMap = ['*'];
        if ($field !== '*') {
            $map = explode(',', $field);
            $cloumnMap = [];
            foreach ($map as $m) {
                $cloumnMap[] = $this->columnQuote($m);
            }
        }
        $map = [];

        $query = 'SELECT '.implode(',', $cloumnMap).' FROM '
        .$this->tableQuote($table).$join_table.$this->whereClause($where, $data);

        return $this->fetchAll($query, $data);
    }

    public function fetchAll($query, $data = [])
    {
        $stmt = $this->query($query, $data);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $result;
    }

    public function get(string $table, string $field, array $where)
    {
        $where['LIMIT'] = [0, 1];
        $data = $this->select($table, $field, $where);
        $result = false;
        if (isset($data[0])) {
            $result = (count($data[0]) > 1) ? $data[0] : array_pop($data[0]);
        }

        return $result;
    }

    public function connect()
    {
        try {
            $option[PDO::ATTR_TIMEOUT] = static::$option['timeout'];
            $option[PDO::ATTR_PERSISTENT] = static::$option['presistent'];
            $this->pdo = new PDO(
                $this->dsn,
                static::$option['username'],
                static::$option['password'],
                $option
            );
            foreach ($this->commands as $value) {
                $this->pdo->exec($value);
            }
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new PDOException($e);
        }

        return $this->pdo;
    }

    public function reconnect()
    {
        $this->pdo = null;

        return $this->connect();
    }

    protected function connection()
    {
        return $this->pdo instanceof PDO ? $this->pdo : $this->connect();
    }

    public function action($actions)
    {
        if (is_callable($actions)) {
            $this->pdo->beginTransaction();
            try {
                $result = $actions($this);
                if ($result === false) {
                    $this->pdo->rollBack();
                } else {
                    $this->pdo->commit();
                }
            } catch (Exception $e) {
                $this->pdo->rollBack();
                throw $e;
            }

            return $result;
        }

        return false;
    }

    public function sql()
    {
        $data = $this->sqldata;
        $string = $this->lastsql;
        if (!$data) {
            return $string;
        }

        $indexed = $data == array_values($data);
        foreach ($data as $k => $v) {
            if (is_string($v)) {
                $v = "'$v'";
            }

            if ($indexed) {
                $string = preg_replace('/\?/', $v, $string, 1);
            } else {
                $string = str_replace("$k", $v, $string);
            }
        }

        return $string;
    }

    private function aggregate(string $type, $table, string $field = '*', array $where = [])
    {
        $join_table = '';
        if (is_array($table)) { //join
            $tables = $table['table'] ?? [];
            $join = $table['join'] ?? '';
            $on = $table['on'] ?? [];
            $table = $tables[0];

            $join_table = " $join ".$this->tableQuote($tables[1]).' ON '.$this->columnQuote($on[0]).'='.$this->columnQuote($on[1]);
        }
        $field = $field == '*' ? '*' : $this->columnQuote($field);
        $query = 'SELECT '.strtoupper($type).'('.$field.') FROM '
        .$this->tableQuote($table).$join_table.$this->whereClause($where, $data);

        $stmt = $this->query($query, $data);

        $number = $stmt->fetchColumn();

        return is_numeric($number) ? $number + 0 : $number;
    }

    public function __call($name, $arguments)
    {
        $aggregation = ['avg', 'count', 'max', 'min', 'sum'];
        if (in_array($name, $aggregation)) {
            array_unshift($arguments, $name);

            return call_user_func_array([$this, 'aggregate'], $arguments);
        }
    }
}
