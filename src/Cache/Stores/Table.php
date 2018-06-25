<?php
namespace Ypf\Cache\Stores;

use Ypf\Cache\CacheInterface;

class Table implements CacheInterface {
    private $table;
    
    public function __construct($size) {
        if (!is_int($size) || (int) $size <= 0) {
            throw new \InvalidArgumentException('Size must be positive integer');
        }
        $this->table = new \swoole_table((int)$size);
        $this->table->column('value', \swoole_table::TYPE_STRING, 65531);
        $this->table->column('expire', \swoole_table::TYPE_INT, 4);
        $this->table->create();
    }
    
    public function set(string $key, $value, int $ttl = -1) {
        $this->table->set($key, [
                    'value' => \swoole_serialize::pack($value),
                    'expire' => $ttl == -1 ? 0 : time() + $ttl,
                ]);
    }
    
    public function get(string $key) {
        if(!$this->table->exist($key)) {
            return false;
        }
        $data = $this->table->get($key);

        if(0 === $data['expire'] || $data['expire'] >= time()) {
            return @\swoole_serialize::unpack($data['value']);
        }
        else{
            $this->delete($key);
        }
        return false;
    }
    
    public function delete(string $key) {
        return $this->table->del($key);
    }
}
