<?php

class TestController {
  
  /**
  * This is some test documentation
  */
  function getTestJsonResponse($req, $res) {
    $res->add(json_encode($req));
    $res->send(200, 'json');
  }
	
}

