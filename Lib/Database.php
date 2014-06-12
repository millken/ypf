<?php

namespace Ypf\Lib;

use \PDO;
use \PDOException;

class Database extends PDO
{

    public function __construct($options = array())
    {
        $default_options = array(
        	'dbtype' => 'mysql',
        	'host' => '127.0.0.1',
        	'port' => 3306,
        	'dbname' => 'test',
        	'username' => 'root',
        	'password' => '',
        	'charset' => 'utf8',
        );
        $options = array_merge($default_options, $options);
        $dsn = $this->createdsn($options);
        try {
            $option = $options['charset'] ? array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '.$options['charset']) : null;
            parent::__construct(
                $dsn,
                $options['username'],
                $options['password'],
                $option
            );
            parent::setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(Exception $e) {
            echo 'Erreur : '.$e->getMessage().'<br />';
            echo 'N° : '.$e->getCode();
        }
    }
    
	private function createdsn($options)
	{
		return $options['dbtype'] . ':host=' . $options['host'] . ';dbname=' . $options['dbname'] . ';port=' . $options['port'];
	}

    public function executeQuery($query, $data = array())
    {
        $stmt = parent::prepare($query);
        
        try {
            $stmt->execute($data);
            return $stmt;
        }
        catch (PDOException $e) {
            echo $e->getMessage();
            return false;
        }
    }
    
    public function insert($query, $data = array())
    {
        return $this->executeQuery($query, $data) ? ( parent::lastInsertId() ? parent::lastInsertId() : true ) : false;
    }

    public function select($query, $data = array())
    {
        return $this->executeQuery($query, $data);
    }
    
    public function update($query, $data = array())
    {
        return $this->executeQuery($query, $data);
    }

    public function delete($query, $data = array())
    {
        return $this->executeQuery($query, $data);
    }
    
}