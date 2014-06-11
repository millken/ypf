<?php

namespace Cat;

class Controller extends \Ypf\Core\Controller {
	protected $children = array();
	protected $data = array();
	public $template = '';

	public function render($return = false) {
		foreach ($this->children as $key => $child) {
			$this->data[$key] = $this->forward($child);
		}
		$this->view->assign($this->data);
		$this->output = $this->view->display($this->template, true);
		if($return) return true;
		echo($this->output);
	}
}
