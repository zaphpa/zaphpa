<?php

class TestController {

  function getTestJsonResponse($req, $res) {
    $res->add(json_encode($req));
    $res->setFormat("json");
    $res->send(200);
  }
	
}

