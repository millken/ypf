<?php
namespace Ypf\Cli;
/**
 * 
 * 配置
 *
 */
class Master
{
    
    const NAME = 'ypfjobber';
    
    protected static $_masterPid = 0;
    protected static $_configPath = '';
    
    /**
     * server统计信息 ['start_time'=>time_stamp, 'worker_exit_code'=>['worker_name1'=>[code1=>count1, code2=>count2,..], 'worker_name2'=>[code3=>count3,...], ..] ]
     * @var array
     */    
    protected static $serverStatusInfo = array(
        'start_time' => 0,
        'worker_exit_code' => array(),
    );
    
    /**
     * 用于保存所有子进程pid ['worker_name1'=>[pid1=>pid1,pid2=>pid2,..], 'worker_name2'=>[pid3,..], ...]
     * @var array
     */
    protected static $_workpids = array();
    
     public static function run() {

        // 设置进程名称，如果支持的话
        self::setProcessTitle(self::NAME.':master with-config:' . self::$_configPath);
        // 变成守护进程
        self::daemonize();
        // 安装信号
        self::installSignal();
        
        // 保存进程pid
        self::savePid();
        
        // 创建worker进程
        self::createWorkers();

        //self::runTask();        
        // 主循环
        self::loop();        
    }
    
    protected static function runTask() {
        \Ypf\Cli\Task::init();
        foreach(\Ypf\Lib\Config::getAll() as $key => $worker) {
			if(false !== strpos(strtolower($key), 'action')){
				if(isset($worker['status']) && $worker['status']) {
					\Ypf\Cli\Task::add($worker['time_long'], $worker['action'], null, $worker['persistent']);
				}
			}
        }
    }

    /**
     * 安装相关信号控制器
     * @return void
     */
    protected static function installSignal()
    {
        //stop
        pcntl_signal(SIGINT,  array('\Ypf\Cli\Master', 'signalHandler'), false);
        //status
        pcntl_signal(SIGUSR2, array('\Ypf\Cli\Master', 'signalHandler'), false);
        //reload
        pcntl_signal(SIGHUP, array('\Ypf\Cli\Master', 'signalHandler'), false);
        // 设置子进程退出信号处理函数
        //pcntl_signal(SIGCHLD, array('\Ypf\Cli\Master', 'signalHandler'), false);
        pcntl_signal(SIGPIPE, SIG_IGN, false);
    
    }
    
    /**
     * 忽略信号
     * @return void
     */
    protected static function ignoreSignal()
    {
        // 设置忽略信号
        pcntl_signal(SIGPIPE, SIG_IGN);
        pcntl_signal(SIGTTIN, SIG_IGN);
        pcntl_signal(SIGTTOU, SIG_IGN);
        pcntl_signal(SIGQUIT, SIG_IGN);
        pcntl_signal(SIGALRM, SIG_IGN);
        pcntl_signal(SIGINT, SIG_IGN);
        pcntl_signal(SIGUSR1, SIG_IGN);
        pcntl_signal(SIGHUP, SIG_IGN);
    }
    
    /**
     * 设置server信号处理函数
     * @param null $null
     * @param int $signal
     * @return void
     */
    public static function signalHandler($signal)
    {
        switch($signal)
        {
            // 停止server信号
            case SIGINT:
            	self::stopAll();
                break;
            // 测试用
            case SIGUSR1:
                break;
            // 平滑重启server信号
            case SIGHUP:
                echo("Server reloading\n");
                \Ypf\Cli\Task::delAll();
                //\Ypf\Lib\Config::clear();
                //\Ypf\Lib\Config::load(__CONF__);
                //self::runTask();
                break;
        }
    }
    
    public static function setConfigPath($config_path)
    {
    	self::$_configPath = $config_path;
    }
    
    protected static function stopAll()
    {
		 // 主进程部分
		if(self::$_masterPid === posix_getpid())
		{
			foreach((array)self::$_workpids as $work_name => $pids) {
				foreach((array)$pids as $pid) {
					echo "worker({$pid}) [$work_name] exiting...\n";
					posix_kill($pid, SIGKILL );
				}
			}
			exit("master(". self::$_masterPid . ") exiting...\n");
		}else{
			exit(0);
		}
    }
    
    /**
     * 保存主进程pid
     * @return void
     */
    public static function savePid()
    {
        // 保存在变量中
        self::$_masterPid = posix_getpid();
        
        // 保存到文件中，用于实现停止、重启
        if(false === @file_put_contents(YPF_PID_FILE, self::$_masterPid))
        {
            exit("Can not save pid to pid-file(" . YPF_PID_FILE . ")\n\nServer start fail\n\n");
        }
        
        // 更改权限
        chmod(YPF_PID_FILE, 0644);
    }
    
    /**
     * 使之脱离终端，变为守护进程
     * @return void
     */
    protected static function daemonize()
    {
        // 设置umask
        umask(0);
        // fork一次
        $pid = pcntl_fork();
        if(-1 == $pid)
        {
            // 出错退出
            exit("Daemonize fail ,can not fork");
        }
        elseif($pid > 0)
        {
            // 父进程，退出
            exit(0);
        }
        // 子进程使之成为session leader
        if(-1 == posix_setsid())
        {
            // 出错退出
            exit("Daemonize fail ,setsid fail");
        }
    
        // 再fork一次
        $pid2 = pcntl_fork();
        if(-1 == $pid2)
        {
            // 出错退出
            exit("Daemonize fail ,can not fork");
        }
        elseif(0 !== $pid2)
        {
            // 结束第一子进程，用来禁止进程重新打开控制终端
            exit(0);
        }
    
        // 记录server启动时间
        self::$serverStatusInfo['start_time'] = time();
    }
    
    /**
     * 根据配置文件创建Workers
     * @return void
     */
    protected static function createWorkers()
    {
        // 循环读取配置创建一定量的worker进程
        foreach (\Ypf\Lib\Config::getAll() as $worker_name=>$config)
        {
        	if(!$config['status']) continue;
            // 初始化
            if(empty(self::$_workpids[$worker_name]))
            {
                self::$_workpids[$worker_name] = array();
            }
            $pid = self::forkOneWorker($worker_name, $config);
            print("\n($worker_name)pid=". $pid);
            // child exit,may not loop
            if($pid == 0)
            {
                echo(" $worker_name CHILD EXIT ERR : " . print_r($config, true));
            }
        }
    }
    
    /**
     * 创建一个worker进程
     * @param string $worker_name worker的名称
     * @return int 父进程:>0得到新worker的pid ;<0 出错; 子进程:始终为0
     */
    protected static function forkOneWorker($worker_name, $config)
    {
        // 创建子进程
        $pid = pcntl_fork();
        
        // 先处理收到的信号
        pcntl_signal_dispatch();
        
        // 父进程
        if($pid > 0)
        {
            self::$_workpids[$worker_name][$pid] = $pid;
            return $pid;
        }
        // 子进程
        elseif($pid === 0)
        {
            // 忽略信号
            self::ignoreSignal();
                        
    
            // 关闭输出
            self::resetStdFd();
    
            // 尝试设置子进程进程名称
            self::setWorkerProcessTitle($worker_name);
            \Ypf\Ypf::getInstance()->disPatch($config['action'], null);
            return 0;
        }
        // 出错
        else
        {
            echo("create worker fail worker_name:$worker_name detail:pcntl_fork fail");
            return $pid;
        }
    }
    
    public static function getStatus()
    {
		print_r(self::$_workpids);    
    }
    
    /**
     * 获取主进程pid
     * @return int
     */
    public static function getMasterPid()
    {
        return self::$_masterPid;
    }
    
    /**
     * 关闭标准输入输出
     * @return void
     */
    protected static function resetStdFd()
    {
        // 开发环境不关闭标准输出，用于调试
        if(posix_ttyname(STDOUT))
        {
            return;
        }
        global $STDOUT, $STDERR;
        @fclose(STDOUT);
        @fclose(STDERR);
        // 将标准输出重定向到/dev/null
        $STDOUT = fopen('/dev/null',"rw+");
        $STDERR = fopen('/dev/null',"rw+");
    }
    
    /**
     * 设置子进程进程名称
     * @param string $worker_name
     * @return void
     */
    public static function setWorkerProcessTitle($worker_name)
    {
       self::setProcessTitle(self::NAME.":worker $worker_name");
    }
    
    /**
     * 设置进程名称，需要proctitle支持 或者php>=5.5
     * @param string $title
     * @return void
     */
    protected static function setProcessTitle($title)
    {
        // >=php 5.5
        if (version_compare(phpversion(), "5.5", "ge") && function_exists('cli_set_process_title'))
        {
            cli_set_process_title($title);
        }
        // 需要扩展
        elseif(extension_loaded('proctitle') && function_exists('setproctitle'))
        {
            setproctitle($title);
        }
    }
    
    /**
     * 主进程主循环 主要是监听子进程退出、服务终止、平滑重启信号
     * @return void
     */
    public static function loop()
    {
        $siginfo = array();
        while(1)
        {
            @pcntl_sigtimedwait(array(SIGCHLD), $siginfo, 1);
            // 初始化任务系统
            //\Ypf\Cli\Task::tick();
            // 触发信号处理
            pcntl_signal_dispatch();
        }
    }
}
