<?php
namespace Controller\Cli;

class Test extends \Controller\Cli\Common {
	private $log;
	
	public function __construct() {
		//log
		$this->log = new \Ypf\Lib\Log('./debug.log');
		$this->log->SetLevel(0);
	}
	
	public function index(){
        while( 1 ) {
            $msg =  date('Y-m-d H:i:s') . "===" . getmypid();
            $this->log->Info($msg);
            sleep(3);
        }
	}
    
	public function index2(){
		$msg =  date('Y-m-d H:i:s') . "::index2";
	    $this->log->Info($msg);
	}    
	
}
