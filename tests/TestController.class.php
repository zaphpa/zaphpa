<?php

class TestController {
	
	function getUser($req, $res) {
		$res->add(json_encode($req));
		$res->setFormat("json");
		$res->send(200);
	}
	
}

