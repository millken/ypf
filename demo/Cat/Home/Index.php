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
}
