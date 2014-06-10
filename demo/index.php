<?php
require '../Ypf.php';

class test{
	public $a = 1;
	public function s($a){
		$this->a = $a;
	}
	public function g(){
		return$this->a;
	}
}
//\Ypf\Ypf::registerAutoloader();

$app = new \Ypf\Ypf();



$test = new test();
$test->s(2);

$app->set('test', $test);

echo $app->test->g();
echo "<br />+OK";
