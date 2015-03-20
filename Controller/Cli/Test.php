<?php
namespace Controller\Cli;

class Test extends \Controller\Cli\Common {
	
	public function index(){
        while( 1 ) {
            $msg =  date('Y-m-d H:i:s') . "::index1";
            $this->log->Info($msg);
            sleep(3);
        }
	}
    
	public function index2(){
		$msg =  date('Y-m-d H:i:s') . "::index2";
	    $this->log->Info($msg);
	}    
	
}
