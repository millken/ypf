<?php

namespace Cat;

class View extends \Ypf\Core\View {

	private $response;

	public function setResponse($response) {
		$this->response = $response;
	}

	public function display($template) {
		$output = $this->fetch($template);
		if(defined("__MODE__") && __MODE__ == "swoole") {
			$this->response->setOutput($output);
		}else{
			echo $output;
		}
	}
}
