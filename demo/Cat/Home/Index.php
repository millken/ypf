<?php

namespace Cat\Home;

class Index extends \Cat\Controller {

	public function index() {

		$user = new \Model\Login\User();
		//echo $user->add(1, 2);
		$res = $user->getAbc();
		foreach ($res as $key => $value) {
			print_r($value);
		}
		echo date("H:i:s");
		$output = print_r($this->request->get, true);
		$this->log->Debug($output);
		//$this->assgin('a','aa');
		//$this->render();
		$this->response->setOutput($output);
	}

	public function viewtest1() {
		$this->response->addHeader("Server", "Swoole-Ypf");
		//$this->response->setCompression(9);
		$this->view->setResponse($this->response);
		$this->view->setTemplateDir(__APP__ . '/CatView/');
		$this->view->assign('username', 'tester');
		$this->view->assign('header', $this->action("\Cat\Common\Header\index"));
		$this->view->display("index.tpl");
		//$this->response->response->header("Server", "Swoole-Ypf");
		//$this->response->response->end("xx");
	}
}
