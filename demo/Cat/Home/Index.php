<?php

namespace Cat\Home;

class Index extends \Cat\Controller {

	public function index() {
		echo $this->test->g();
		//echo date("Y-m-d H:i:s");
		$this->response->setOutput(($this->request->get['a']));
	}
}
