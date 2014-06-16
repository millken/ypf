<?php
error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);

define("__APP__", dirname(__DIR__));

require '../../Ypf/Ypf.php';

$app = new \Ypf\Ypf();

\Ypf\Lib\Config::instance();

$db = new \Ypf\Lib\Database(\Ypf\Lib\Config::get('db.dev', true));
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

$app->addPreAction("Cat\Common\Router\index");

$app->disPatch();

$response->output();
