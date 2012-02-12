<?php

class TestController {
	
	function getUserByNumericId($req, $res) {
		$res->add(json_encode($req));
		$res->setFormat("json");
		$res->send(200);
	}
	
	function getTagByAlphaId($req, $res) {
		$res->add(json_encode($req));
		$res->setFormat("json");
		$res->send(200);
	}
	
}

