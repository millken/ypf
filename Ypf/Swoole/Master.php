<?php
namespace Ypf\Swoole;
/**
 * 
 * 配置
 *
 */
class Master
{
    
    const NAME = 'ypfworker';
    
    protected static $_statusInfo = array();
    protected static $_masterPid = 0;
    protected static $_configPath = '';

    //用于保存所有子进程pid 
    protected static $_workpids = array();

    //记录pid重启次数，5秒内超过10次则不再重启
    protected static $_worknames = array();
    
     public static function run() {

        // 设置进程名称，如果支持的话
        swoole_set_process_name(self::NAME.':master with-config:' . self::$_configPath);

        // 变成守护进程
        //\swoole_process::daemon(true, true);
                
        // 保存进程pid
        self::savePid();
        
        // 创建worker进程
        self::createWorkers();
            
        // 监测worker
        self::monitorWorkers();        
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
     * 根据配置文件创建Workers
     * @return void
     */
    protected static function createWorkers()
    {
    	$serv = new \swoole_server("/var/run/ypf.sock", 0, SWOOLE_BASE, SWOOLE_UNIX_STREAM);
		$serv->set(array(
			'task_worker_num' => 10, //task num
			'worker_num' => 2,    //worker num
		));    	
        // 循环读取配置创建一定量的worker进程
        foreach (\Ypf\Lib\Config::getAll() as $worker_name=>$config)
        {
        	if(!$config['status']) continue;
            self::forkOneWorker($worker_name, $config);
        }
        $serv->on("Receive",function() {    try    { }catch(Exception $e){ }});
        $serv->on("Task", "\Ypf\Swoole\Task::task");
        $serv->on("Finish", "\Ypf\Swoole\Task::finish");
        $serv->on('WorkerStart', function ($serv, $worker_id){
            \Ypf\Swoole\Task::setServe($serv);
        	if($worker_id >= $serv->setting['worker_num']) {
        		\swoole_set_process_name(self::NAME.":task_worker $worker_id");
        	}else{
        		\swoole_set_process_name(self::NAME.":task_master $worker_id");
        	}
        });       
        $serv->start();
    }
    
    /**
     * 创建一个worker进程
     * @param string $worker_name worker的名称
     * @return int 父进程:>0得到新worker的pid ;<0 出错; 子进程:始终为0
     */
    protected static function forkOneWorker($worker_name, $config)
    {
        $process = new \swoole_process(function(\swoole_process $worker){
        	$recv = $worker->pop();
        	$worker_config = unserialize($recv);
        	extract($worker_config);

    		//echo "From Master: ".print_r($worker_config, true)."\n";
    		//sleep(2);
   			//$worker->exit(0);
	        \swoole_set_process_name(self::NAME.":single worker $worker_name");
        	\Ypf\Ypf::getInstance()->disPatch($config['action'], array('worker_name' => $worker_name));
        }, false, false);
        $process->useQueue();
        $pid = $process->start();
        $process->push(serialize(array('pid' => $pid, 'worker_name' => $worker_name, 'config' => $config)));
        
        // 父进程
        if($pid > 0)
        {
            self::$_workpids[$pid] = $worker_name;
            echo("starting worker : $worker_name        [ OK ]\n");
        }
        else
        {
            echo("starting worker : $worker_name        [ FAIL ]  '"  . swoole_strerror( swoole_errno()) . "' \n");
        }
        return $pid;
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
    

    protected static function monitorWorkers() {
        while(1) {
            $result = \swoole_process::wait();
            extract($result);
            //$result = array('code' => 0, 'pid' => 15001, 'signal' => 15);
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
