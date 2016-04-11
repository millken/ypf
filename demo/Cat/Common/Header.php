<?php

namespace Cat\Common;

class Header extends \Cat\Controller {
	public function index() {
		$view = new \Cat\View($this);
		$view->setTemplateDir(__APP__ . '/CatView/');
		$view->assign('title', 'This is a header variable!');
		return $view->fetch("header.tpl");		
	}
}