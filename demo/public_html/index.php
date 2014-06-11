<?php
define("__APP__", dirname(__DIR__));

require '../../Ypf.php';


class test{
	public $a = 1;
	public function s($a){
		$this->a = $a;
	}
	public function g(){
		return$this->a;
	}
}

$app = new \Ypf\Ypf();

\Ypf\Lib\Config::instance();


$test = new test();
$test->s(2);

$response = new \Ypf\Lib\Response();
$app->set('test', $test);
$app->set('request', new \Ypf\Lib\Request());
$app->set('response', $response);

$app->addPreAction("Cat\Common\Router\index");
//$app->addPreAction(array('\Cat\Home\Index', 'index'));
$app->disPatch();

$response->output();
