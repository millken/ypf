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
    
    protected static $_statusInfo = array();
    protected static $_masterPid = 0;
    protected static $_configPath = '';

    //用于保存所有子进程pid 
    protected static $_workpids = array();

    //记录pid重启次数，5秒内超过10次则不再重启
    protected static $_worknames = array();
    
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
    
        // 监测worker
        self::monitorWorkers();        
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
        pcntl_signal(SIGPIPE, SIG_IGN, false);
    
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
            self::$_masterPid = 0;
			foreach((array)self::$_workpids as $pid => $work_name) {
				echo "stopping worker:  $work_name         [  OK  ]\n";
				posix_kill($pid, SIGKILL );
			}
			echo "stopping master:           [  OK  ]\n";
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
        self::$_statusInfo['start_time'] = time();
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
            self::forkOneWorker($worker_name, $config);
        }
    }
    
    /**
     * 创建一个worker进程
     * @param string $worker_name worker的名称
     * @return int 父进程:>0得到新worker的pid ;<0 出错; 子进程:始终为0
     */
    protected static function forkOneWorker($worker_name, $config)
    {
        $pid = pcntl_fork();
        
        
        // 父进程
        if($pid > 0)
        {
            self::$_workpids[$pid] = $worker_name;
            echo("starting worker : $worker_name        [ OK ]\n");
        }
        // 子进程
        elseif($pid === 0)
        {
            self::$_workpids = array();
            // 尝试设置子进程进程名称
            self::setWorkerProcessTitle($worker_name);
            \Ypf\Ypf::getInstance()->disPatch($config['action'], array('worker_name' => $worker_name));
            exit(250);
        }
        // 出错
        else
        {
            echo("starting worker : $worker_name        [ FAIL ] \n");
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

    protected static function monitorWorkers() {
        while(1) {
            // 如果有信号到来，尝试触发信号处理函数
            pcntl_signal_dispatch();
            // 挂起进程，直到有子进程退出或者被信号打断
            $pid = pcntl_wait($status, WUNTRACED);
            // 有子进程退出
            if($pid > 0 && self::$_masterPid)
            {
                $config_all = \Ypf\Lib\Config::getAll();
                $worker_name = self::$_workpids[$pid];
                $config = $config_all[$worker_name];
                unset(self::$_workpids[$pid]);
                //若worker 5秒内spawn的次数超过10次，则停止spawn
                if(!isset(self::$_worknames[$worker_name]))
                    self::$_worknames[$worker_name] = array(
                    'restart_nums' => 0, 
                    'restart_time' => 0
                    );
                ++self::$_worknames[$worker_name]['restart_nums'];
                $stopworker = false;
                if(self::$_worknames[$worker_name]['restart_nums'] % 10 == 0) {
                    if(time() - self::$_worknames[$worker_name]['restart_time'] <= 5) {
                        $stopworker = true;
                    }
                    self::$_worknames[$worker_name]['restart_time'] = time();
                }
                if(!$stopworker) {
                    if($config['status']);
                    self::forkOneWorker($worker_name, $config);                       
                }
            }elseif(!self::$_masterPid){
                exit(0);
            }
        }            
    }
    
}
