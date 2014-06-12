<?php
define("__APP__", dirname(__DIR__));

require '../../Ypf.php';

$app = new \Ypf\Ypf();

\Ypf\Lib\Config::instance();


$response = new \Ypf\Lib\Response();

$db = new \Ypf\Lib\Database(\Ypf\Lib\Config::get('db.test', true));
$app->set('db', $db);

$app->set('request', new \Ypf\Lib\Request());
$app->set('response', $response);

$app->addPreAction("Cat\Common\Router\index");

$app->disPatch();

$response->output();
