<?php
namespace Ypf\Cache\Stores;

use Ypf\Cache\CacheInterface;

class File implements CacheInterface {
	protected $prefix='';
	protected $path = '';

	function __construct($path = '/tmp/', $prefix = 'ypf_') {
		if (!is_dir($path) && !mkdir($path)) {
			throw new \InvalidArgumentException("path: '{$path}' not exists");
		}
		$this->path = $path;
        $this->prefix = $prefix;
	}

    private function getFileName($key) {
        return $this->path . $this->prefix . md5($key);
    }

	public function set(string $key, $value, int $ttl = -1) {
		$data = [
			'value' => $value,
			'expire' => ($ttl > 0 ? time() + $ttl : $ttl),
		];
        file_put_contents($this->getFileName($key), serialize($data));
		// Garbage collection
		if(mt_rand(1, 100) === 100)	{
			$this->gc();
		}
	}

    public function get(string $key) {
        $file = $this->getFileName($key);
        return $this->data($file);
    }

    private function data($file) {
        if(!file_exists($file) || !is_readable($file)) {
            return false;
        }
        $content = file_get_contents($file);
        $data = @unserialize($content);
        if (!$data) {
            unlink($file);
            return false;
        }
        if(isset($data['expire']) && ($data['expire'] == -1 || $data['expire'] >= time())) {
            return $data['value'];
        }else{
            unlink($file);            
        }
    }

    public function delete(string $key) {
        $file = $this->getFileName($key);
        if(file_exists($file)) {
            unlink($file);
        }
        return true;  
    }

    private function gc() {
        $files = glob($this->path . $this->prefix . "*");
        foreach($files as $file) {
            $this->data($file);
        }
    }
}
