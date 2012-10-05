<?php

class TestController {
  
  /**
   * This is some test documentation
   */
  function getTestJsonResponse($req, $res) {
    $res->add(json_encode($req));
    $res->send(200, 'json');
  }
  
  function getQueryVarTestJsonResponse($req, $res) {
    $response = $req;
    $response->test_param = $req->get_var('test_param');
    $res->add(json_encode($response));
    $res->send(200, 'json');
  }
	
}
