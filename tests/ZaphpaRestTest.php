<?php

require_once (__DIR__ . '/ZaphpaTestCase.class.php');
require_once (__DIR__ . '/ZaphpaRestClient.class.php');

/**
* Functional tests for basic endpoint processing
*/
class ZaphpaRestTest extends ZaphpaTestCase {

  private $rest_client;
  
  public function setUp() {
    parent::setUp();
    $this->rest_client = ZaphpaRestClient::get_instance($this->server_url);
  }

  public function test_get_user_by_id() {
		$resp = $this->rest_client->http_get('users/1');
		$this->assertEquals(1, $resp->decoded->params->id, "User Get Test: id numeric check");
		
		try {
			$resp = $this->rest_client->http_get('users/alpha');
		} catch (ZaphpaRestClientException $ex) {
			return;
		}
		
		$this->fail('User get test: alpha argument should not have passed.');				
	}  

  public function test_get_tag_by_id() {
		$resp = $this->rest_client->http_get('tags/alpha');
		$this->assertEquals('alpha', $resp->decoded->params->id, "Tag Get Test: id alpha check");
		
		try {
			$resp = $this->rest_client->http_get('tags/1');
		} catch (ZaphpaRestClientException $ex) {
			return;
		}
		
		$this->fail('User get test: numeric argument should not have passed.');				
		
	}  

}