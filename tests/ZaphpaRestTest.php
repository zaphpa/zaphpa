<?php

require_once (__DIR__ . '/ZaphpaTestCase.class.php');
require_once (__DIR__ . '/ZaphpaRestClient.class.php');

class ZaphpaRestTest extends ZaphpaTestCase {

  private $rest_client;
  
  public function setUp() {
    parent::setUp();
    $this->rest_client = ZaphpaRestClient::get_instance($this->server_url);
  }

  public function test_get_entity() {
		$resp = $this->rest_client->http_get('users/1');
		$this->assertEquals(1, print_r($resp->decoded->params->id, true), "Entity Get Test: id check");
	}  

}