<?php

namespace Model\Login;

class User extends \Model\Model {

	public function add($a, $b) {
		return $a + $b;
	}

	public function getAbc() {
		$res = $this->db->fetch_all('abc');
		return $res;
	}
}