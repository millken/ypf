<?php

namespace Ypf\Swoole;

class Cmd {
    static $serverConfig;
    static $masterPidFile;
    static $masterPid;
    public static function start($serverConfig) {
        global $argv;
        static::$serverConfig = $serverConfig['server'];
        static::$masterPidFile = isset(static::$serverConfig['pid_file']) ?
        static::$serverConfig['pid_file'] : '/tmp/ypf.pid';
        static::$masterPid = @file_get_contents(static::$masterPidFile);
        static::envCheck();
        if (empty($argv[1])) {
            static::usage();
        }
        switch ($argv[1]) {
        case 'start':
            echo "server starting ..." . PHP_EOL;
            break;
        case 'reload':
            static::reload();
            break;
        case 'stop':
            static::stop();
            exit;
            break;
        case 'restart':
            static::restart();
            break;
        case 'kill':
            static::kill();
            break;
        default:
            static::usage();
            break;
        }
    }

    public static function envCheck() {
        if (!extension_loaded('swoole')) {
            exit("swoole extension must be installed: https://github.com/swoole/swoole-src\n");
        }
    }

    public static function kill() {
        $rets = $match = array();

        $process_lists = [
            (isset(static::$serverConfig['master_process_name']) ?
                static::$serverConfig['master_process_name'] : 'ypf:swoole-master'),
            (isset(static::$serverConfig['worker_process_name']) ?
                static::$serverConfig['worker_process_name'] : 'ypf:swoole-worker-%d'),
            (isset(static::$serverConfig['task_worker_process_name']) ?
                static::$serverConfig['task_worker_process_name'] : 'ypf:swoole-task-worker-%d'),
            (isset(static::$serverConfig['cron_worker_process_name']) ?
                static::$serverConfig['cron_worker_process_name'] : 'ypf:swoole-cron-worker'),
        ];
        foreach ($process_lists as $i => $process_name) {
            $process_name = str_replace("%d", "", $process_name);
            exec("ps aux | grep -E '" . $process_name . "' | grep -v grep", $rets[$i]);
        }
        $this_pid = posix_getpid();
        $this_ppid = posix_getppid();
        foreach ($rets as $ret) {
            foreach ($ret as $line) {
                if (preg_match("/^[\S]+\s+(\d+)\s+/", $line, $match)) {
                    $tmp_pid = $match[1];
                    if ($this_pid != $tmp_pid && $this_ppid != $tmp_pid) {
                        posix_kill($tmp_pid, 9);
                    }
                }
            }
        }

        exit("server killed ..." . PHP_EOL);
    }

    public static function restart() {
        static::stop();
        $loop = 20;
        while (file_exists(static::$masterPidFile)) {
            $masterPid = @file_get_contents(static::$masterPidFile);
            if (static::$masterPid != $masterPid || $loop < 0) {
                break;
            }
            $loop--;
            clearstatcache(true, static::$masterPidFile);
            usleep(500000);
        }
        if (file_exists(static::$masterPidFile)) {
            echo "failed to restart, some processes are still running ..." . PHP_EOL;
            exit;
        } else {
            echo "server restarting ..." . PHP_EOL;
        }
    }

    public static function reload() {
        if (is_numeric(static::$masterPid)) {
            posix_kill(static::$masterPid, 10);
            echo "server reload ..." . PHP_EOL;
        } else {
            echo "server not running ..." . PHP_EOL;
        }
        exit;
    }

    public static function stop() {
        if (is_numeric(static::$masterPid)) {
            posix_kill(static::$masterPid, 15);
            echo "server stoping ..." . PHP_EOL;
        } else {
            echo "server not running ..." . PHP_EOL;
        }
    }

    public static function usage() {
        global $argv;
        echo "Usage: {$argv[0]} {start|stop|restart|reload|kill|status}" . PHP_EOL;
        exit;
    }
}
