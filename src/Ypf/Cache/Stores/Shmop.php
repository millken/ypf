<?php
namespace Ypf\Cache\Stores;

use Ypf\Cache\CacheInterface;

class Shmop implements CacheInterface {
    protected $id;
    protected $res;
    protected $data;
    protected $changed = false;

    public function __construct($id = null, $startSize = 10240) {
		if (function_exists("shmop_open") === FALSE) {
			die("\nTo use shmop you will need to compile PHP with the --enable-shmop parameter in your configure line.\n");
		}

        $this->id = $id ?? ftok(__FILE__, 't');
        $this->res = shmop_open($id, 'c', 0644, $startSize);
        $data = trim(shmop_read($this->res, 0, shmop_size($this->res)));
        if (empty($data))
            $this->data = array();
        else if (!is_array($this->data = unserialize($data)))
            $this->data = array();
    }

    public function search($query) {
        $result = array();
        foreach ($this->data as $key => $_) {
            if (stripos($key, $query) !== false) {
                $result[] = $key;
            }
        }
        return $result;
    }

    public function check($key) {
        return array_key_exists($key, $this->data);
    }

    public function set(string $key, $value, int $ttl = -1) {
        $this->data[$key] = [($ttl > 0 ? time() + $ttl : $ttl), $value];
        $this->changed = true;
    }

    public function get(string $key) {
        $data = array_key_exists($key, $this->data) ? $this->data[$key] : false;
        if($data === false) {
            return false;
        }
        list($time, $value) = $data;
        if ($time == -1 || $time >= time()) {
            return $value;
        }else{
            $this->delete($key);
        }
        return false;
    }

    public function delete(string $key) {
        if (array_key_exists($key, $this->data)) {
            unset($this->data[$key]);
            $this->changed = true;
        }
    }

    public function keys() {
        return array_keys($this->data);
    }

    public function clear() {
        $this->data = array();
        $this->changed = true;
    }

    public function count() {
        return count($this->data);
    }

    public function __destruct() {
        if ($this->changed) {
            $serialized = serialize($this->data);
            if (strlen($serialized) > shmop_size($this->res)) {
                shmop_delete($this->res);
                $this->res = shmop_open($id, 'c', 0644, ceil(strlen($serialized) * 1.25));
            }
            shmop_write($this->res, $serialized, 0);
        }
        shmop_close($this->res);
    }
}