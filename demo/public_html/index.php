<?php
error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);

define("__APP__", dirname(__DIR__));

require '../../Ypf/Ypf.php';

$ypfSetting = array(
	'root' => __APP__,
	);
$app = new \Ypf\Ypf($ypfSetting);

$config = new \Ypf\Lib\Config(__APP__ . '/conf.d/');
$app->set('config', $config);

$db = new \Ypf\Lib\Database($config->get('db.dev'));
$app->set('db', $db);

//request
$app->set('request', new \Ypf\Lib\Request());

//response
$response = new \Ypf\Lib\Response();
$app->set('response', $response);

//log
$log = new \Ypf\Lib\Log('logs/debug.log');
$log->SetLevel(0);
$app->set('log', $log);

$app->addPreAction("Cat\Common\Init\config");
$app->addPreAction("Cat\Common\Router\index");

$app->disPatch();

$response->output();
