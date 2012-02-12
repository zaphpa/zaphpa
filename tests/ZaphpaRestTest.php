<?php

require_once (__DIR__ . '/ZaphpaTestCase.class.php');

class ZaphpaRestTest extends ZaphpaTestCase {

  private $rest_client;
  
  public function setUp() {
    parent::setUp();
    //$this->rest_client = SetteeRestClient::get_instance($this->db_url);
  }

  public function test_get_entity() {
		echo "success " . $this->server;
	}  
}