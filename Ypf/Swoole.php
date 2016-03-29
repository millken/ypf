<?php
/**
 * Ypf - a micro PHP 5 framework base swoole
 */

namespace Ypf;

class Swoole extends Ypf {

    const VERSION = '0.0.2';

    private $serverConfig;

	private $server;
		
	public function setServerConfigIni($serverConfigIni) {
        if (!is_file($serverConfigIni)) {
            trigger_error('Server Config File Not Exist!', E_USER_ERROR);
        }
        $serverConfig = parse_ini_file($serverConfigIni, true);
        if (empty($serverConfig)) {
            trigger_error('Server Config Content Empty!', E_USER_ERROR);
        }
        $this->serverConfig = $serverConfig;
    }
	
	public function start() {
		$this->server = new \swoole_http_server("127.0.0.1", 9002);
        $this->server->on('Start', array($this, 'onStart'));
        $this->server->on('ManagerStart', array($this, 'onManagerStart'));
        $this->server->on('WorkerStart', array($this, 'onWorkerStart'));
        $this->server->on('WorkerStop', array($this, 'onWorkerStop'));
        $this->server->on('request', array($this, 'onRequest'));
        $this->server->start();	
	}
	
    public function onStart(\swoole_http_server $server) {
    	$name = isset($this->serverConfig['server']['master_process_name']) ? 
    	$this->serverConfig['server']['master_process_name'] : 'ypf:swoole-master';
        \swoole_set_process_name($name);
        return true;
    }

    public function onManagerStart(\swoole_http_server $server) {
    	$name = isset($this->serverConfig['server']['manager_process_name']) ? 
    	$this->serverConfig['server']['manager_process_name'] : 'ypf:swoole-worker';
        \swoole_set_process_name($name);
        return true;
    }

    public function onWorkerStart(\swoole_http_server $server, $workerId) {
        //rename
        $name = isset($this->serverConfig['server']['event_worker_process_name']) ? 
    	$this->serverConfig['server']['event_worker_process_name'] : 'ypf:swoole-event-worker-%d';
        $processName = sprintf($name, $workerId);
        \swoole_set_process_name($processName);
        return true;
    }

    public function onWorkerStop(\swoole_http_server $server, $workerId) {
        return true;
    }

    public function onRequest(\swoole_http_request $request, \swoole_http_response $response) {
        $this->request->init($request);
        $this->response->init($response);
        $this->disPatch();
        $this->response->output();
        //$response->end("<h1>Hello Swoole. #".rand(1000, 9999)."</h1>");
    }
    
}
