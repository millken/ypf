<?php
namespace Ypf;

use \Cron;

define('SWOOLE_SERVER', true);

class Swoole extends Ypf {

    const LISTEN = '127.0.0.1:9002';
    const MODE = 'base';

    private $serverConfig;
    private $workerConfig;

    public $server;
    private $worker_pid = [];
    private $pidFile;
    private $table;
    private static $isSpawnWorker;

    public function setServerConfigIni($serverConfigIni) {
        if (!is_file($serverConfigIni)) {
            trigger_error("Server Config File Not Exist: {$serverConfigIni}", E_USER_ERROR);
        }
        $serverConfig = parse_ini_file($serverConfigIni, true);
        if (empty($serverConfig)) {
            trigger_error('Server Config Content Empty!', E_USER_ERROR);
        }
        $this->serverConfig = $serverConfig;
        $this->pidFile = isset($this->serverConfig['server']['pid_file']) ?
        $this->serverConfig['server']['pid_file'] : '/tmp/ypf.pid';
        \Ypf\Swoole\Cmd::start($serverConfig);
    }

    public function setWorkerConfigPath($path) {
        if (!is_dir($path)) {
            trigger_error('Worker Config Path Not Exist!', E_USER_ERROR);
        }

        foreach (glob($path . '/*.conf') as $config_file) {
            $config = parse_ini_file($config_file, true);
            $name = basename($config_file, '.conf');
            $this->workerConfig[$name] = $config;
        }
    }

    public function &getServer() {
        return $this->server;
    }

    public function &getTable() {
        return $this->table;
    }

    public function start() {
        static::$isSpawnWorker = false;
        $this->table = new \Ypf\Cache\Stores\Table(1024);
        $listen = isset($this->serverConfig["server"]["listen"]) ?
        $this->serverConfig["server"]["listen"] : static::LISTEN;
        $mode = isset($this->serverConfig["server"]["mode"]) ?
        $this->serverConfig["server"]["mode"] : static::MODE;
        if ($mode == 'process') {
            $swoole_mode = SWOOLE_PROCESS;
        }else{
            $swoole_mode = SWOOLE_BASE;
        }
        list($addr, $port) = explode(":", $listen, 2);
        $this->server = new \swoole_http_server($addr, $port, $swoole_mode, SWOOLE_TCP);
        if (isset($this->serverConfig["server"]["ssl_listen"])) {
            list($ssl_addr, $ssl_port) = explode(":", $this->serverConfig["server"]["ssl_listen"], 2);
            $this->server->addlistener($ssl_addr, $ssl_port, SWOOLE_TCP | SWOOLE_SSL);
        }
        isset($this->serverConfig['swoole']) && $this->server->set($this->serverConfig['swoole']);

        $this->server->on('Task', [$this, 'onTask']);
        $this->server->on('Finish', [$this, 'onFinish']);
        $this->server->on('Start', [$this, 'onStart']);
        $this->server->on('ManagerStart', [$this, 'onManagerStart']);
        $this->server->on('ManagerStop', [$this, 'onManagerStop']);
        $this->server->on('WorkerStart', [$this, 'onWorkerStart']);
        $this->server->on('WorkerStop', [$this, 'onWorkerStop']);
        $this->server->on('WorkerError', [$this, 'onWorkerError']);
        $this->server->on('Request', [$this, 'onRequest']);
        $this->server->on('PipeMessage', [$this, 'onPipeMessage']);
        $this->server->on('ShutDown', [$this, 'onShutDown']);
        $this->server->start();
    }

    private function createCustomWorker() {
        if (empty($this->workerConfig)) {
            return;
        }

        foreach ($this->workerConfig as $worker_name => $config) {
            if (!$config["status"] || isset($config["crontab"])) {
                continue;
            }

            $this->spawnCustomWorker($worker_name, $config);
        }
        $this->spawnCrontabWorker($this->workerConfig);
        $this->table->set('worker_pid', $this->worker_pid);
    }

    public function crontabWorker() {
        $cron_queue = $this->table->get("worker_cron_queue");
        $cron_ready = $this->table->get("worker_cron_ready");

        if (empty($cron_queue)) {
            return;
        }

        foreach ($cron_queue as $worker_name => $config) {
            $crontab = \Cron\CronExpression::isValidExpression($config["crontab"]);
            if (!$crontab) {
                $timeNs = intval($config["crontab"]);
            } else {
                $cron = \Cron\CronExpression::factory($config["crontab"]);
                $nextRunTime = $cron->getNextRunDate()->getTimestamp();
                $timeNs = intval($nextRunTime - time());
            }
            if ($timeNs < 1) {
                continue;
            }

            swoole_timer_after(1000 * $timeNs, function () use ($worker_name, $config) {
                $a = new \Ypf\Core\Action($config['action'], array('worker_name' => $worker_name));
                if(!$a->execute()) {
                    die ("execute : {$config['action']}        [ Fail ]\n");
                }
                $cron_queue = $this->table->get("worker_cron_queue");
                $cron_ready = $this->table->get("worker_cron_ready");
                unset($cron_ready[$worker_name]);
                $cron_queue[$worker_name] = $config;
                $this->table->set("worker_cron_queue", $cron_queue);
                $this->table->set("worker_cron_ready", $cron_ready);
            });
            unset($cron_queue[$worker_name]);
            $cron_ready[$worker_name] = $config;
        }
        $this->table->set("worker_cron_queue", $cron_queue);
        $this->table->set("worker_cron_ready", $cron_ready);
    }

    private function spawnCrontabWorker($config) {
        $process = new \swoole_process(function () use ($config) {
            foreach ($config as $k => $v) {
                if (!$v["status"] || !isset($v["crontab"])) {
                    unset($config[$k]);
                }
            }
            $this->table->set("worker_cron_queue", $config);
            \swoole_timer_tick(1000, [$this, 'crontabWorker']);
            $processName = isset($this->serverConfig['server']['cron_worker_process_name']) ?
            $this->serverConfig['server']['cron_worker_process_name'] : 'ypf:swoole-cron-worker';
            \swoole_set_process_name($processName);
        }, false, false);
        $pid = $process->start();

        $this->worker_pid[] = $pid;
        if ($pid > 0) {
            echo "starting cron worker     [ OK ]\n";
        } else {
            echo "starting cron worker     [ FAIL ]  '" . \swoole_strerror(swoole_errno()) . "' \n";
        }
    }

    private function spawnCustomWorker($worker_name, $config) {
        $process = new \swoole_process(function (\swoole_process $worker) {
            $recv = $worker->pop();
            $worker_config = unserialize($recv);
            extract($worker_config);

            $name = isset($this->serverConfig['server']['worker_process_name']) ?
            $this->serverConfig['server']['worker_process_name'] : 'ypf:swoole-worker-%d';
            $processName = sprintf("$name:%s", 0, $worker_name);
            \swoole_set_process_name($processName);
            $a = new \Ypf\Core\Action($config['action'], array('worker_name' => $worker_name));
            if($a->execute() instanceof \Exception) {
                die ("execute : {$config['action']}        [ Fail ]\n");
            }
        }, false, false);
        $process->useQueue();
        $pid = $process->start();
        $this->worker_pid[] = $pid;
        $process->push(serialize(array('pid' => $pid, 'worker_name' => $worker_name, 'config' => $config)));

        if ($pid > 0) {
            echo "starting worker : $worker_name        [ OK ]\n";
        } else {
            echo "starting worker : $worker_name        [ FAIL ]  '" . \swoole_strerror(swoole_errno()) . "' \n";
        }
    }

    public function onStart(\swoole_http_server $server) {
        $name = isset($this->serverConfig['server']['master_process_name']) ?
        $this->serverConfig['server']['master_process_name'] : 'ypf:swoole-master';
        \swoole_set_process_name($name);
        file_put_contents($this->pidFile, $server->master_pid);
        return true;
    }

    public function onManagerStart(\swoole_http_server $server) {
        $name = isset($this->serverConfig['server']['manager_process_name']) ?
        $this->serverConfig['server']['manager_process_name'] : 'ypf:swoole-manager';
        \swoole_set_process_name($name);
        return true;
    }

    public function onManagerStop(\swoole_http_server $server) {

        return true;
    }

    public function onWorkerStart(\swoole_http_server $server, $worker_id) {
        if ($worker_id >= $server->setting['worker_num']) {
            $name = isset($this->serverConfig['server']['task_worker_process_name']) ?
            $this->serverConfig['server']['task_worker_process_name'] : 'ypf:swoole-task-worker-%d';
            $processName = sprintf($name, $worker_id);
        } else {
            if (!$worker_id && !static::$isSpawnWorker) {
                $this->createCustomWorker();
                static::$isSpawnWorker = false;
            }
            $name = isset($this->serverConfig['server']['worker_process_name']) ?
            $this->serverConfig['server']['worker_process_name'] : 'ypf:swoole-worker-%d';
            $processName = sprintf($name, $worker_id);
        }
        \swoole_set_process_name($processName);

        return true;
    }

    public function onWorkerError(\swoole_http_server $server, int $worker_id, int $worker_pid, int $exit_code, int $signal) {
        error_log("workerError: $worker_id exit $exit_code", 3, $this->serverConfig['server']['log_file']);
    }

    public function onTask(\swoole_http_server $server, $task_id, $from_id, $data) {
        $data = unserialize($data);
        $result = call_user_func_array($data["func"], $data["args"]);
        return array('callback' => $data['callback'], 'result' => $result, 'thread' => $data['thread']);
       
    }

    public function onFinish(\swoole_http_server $server, $task_id, $data) {
        if (!empty($data['thread'])) {
            $key = $data['thread']['task'];
            $value = $this->table->get($key);
            if (!$value) {
                return;
            }

            $value['results'][$data['thread']['id']] = $data['result'];
            $value['tasks'] -= 1;

            $this->table->set($key, $value);
        }

        if ($data['callback']) {
            $data["args"] = array('task_id' => $task_id, 'result' => $data['result']);
            call_user_func_array($data["callback"], $data["args"]);
        }
    }

    public function onWorkerStop(\swoole_http_server $server, $worker_id) {
        if ($worker_id) {
            return true;
        }
        $worker_pids = $this->table->get('worker_pid');
        foreach ($worker_pids as $worker_pid) {
            \swoole_process::kill($worker_pid, 9);
        }
        $this->table->del("worker_pid");

        return true;
    }

    public function onShutDown(\swoole_http_server $server) {
        @unlink($this->pidFile);
        return true;
    }

    public function onPipeMessage(\swoole_http_server $server, $worker_id, $data) {
        $server->task($data);
    }

    public function onRequest(\swoole_http_request $request, \swoole_http_response $response) {
        $this->request->init($request);
        $this->response->init($response);
        $this->disPatch();
        $this->response->output();
    }
}
