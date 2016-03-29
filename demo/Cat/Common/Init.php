<?php

namespace Cat\Common;

class Init extends \Cat\Controller {
	public function config() {
		$config = require __APP__ . "/conf.d/" . 'config.php';
		$this->config->set('db.pad.config', $config);
		//print_r($this->request->get);
		//print_r($this->config->get('db.pad.config'));
	}
}
