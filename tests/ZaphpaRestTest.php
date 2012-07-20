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

  public function test_pattern_num_single() {
		$resp = $this->rest_client->http_get('users/1');
		$this->assertEquals(1, $resp->decoded->params->id, "User Get Test: id numeric check");
		
		try {
			$resp = $this->rest_client->http_get('users/alpha');
		} catch (ZaphpaRestClientException $ex) {
			return;
		}
		
		$this->fail('User get test: alpha argument should not have passed.');				
	}  

  public function test_pattern_alpha_single() {
    try {
      $resp = $this->rest_client->http_get('tags/alpha');
      $this->assertEquals('alpha', $resp->decoded->params->id, "Tag Get Test: id alpha check");
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Tag get test: alpha argument should have passed.');
    }

    try {
      $resp = $this->rest_client->http_get('tags/234234');
      $this->assertEquals('234234', $resp->decoded->params->id, "Tag Get Test: id numeric check");
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Tag get test: numeric argument should have passed.');
    }

		try {
			$this->rest_client->http_get('tags/aa#$%^sff');
		} catch (ZaphpaRestClientException $ex) {
      $this->assertEquals(true, true, "Tag Special Characters Test: exceptions must fire");
			return;
		}
		
		$this->fail('User get test: special characters argument should not have passed.');
		
	}

  public function test_num_and_alpha_two_params() {

    try {
      $resp = $this->rest_client->http_get('users/1234/books/shakespear');
      $this->assertEquals('1234', $resp->decoded->params->user_id);
      $this->assertEquals('shakespear', $resp->decoded->params->book_id);
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Two-param test: user numeric, book alpha should have passed.');
    }

    try {
      $resp = $this->rest_client->http_get('users/1234/books/35345');
      $this->assertEquals('1234', $resp->decoded->params->user_id);
      $this->assertEquals('35345', $resp->decoded->params->book_id);
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Two-param test: user numeric, book numeric should have passed.');
    }

    try {
      $this->rest_client->http_get('users/asfksalfjk/books/35345');
    } catch (ZaphpaRestClientException $ex) {
      $this->assertEquals(true, true, "Two-param test, user_id is alpha, exception should fire");
      return;
    }

    $this->fail('Two-param test: user_id alpha should not have passed.');

  }
  
  public function test_middleware() {
    try {
      $resp = $this->rest_client->http_get('middlewaretest/777');
      $this->assertEquals(777, $resp->decoded->params->mid, "Middleware response test");
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Middleware test: response test should have passed.');
    }  	
    
    try {
      $resp = $this->rest_client->http_get('middlewaretest/777');
      $this->assertEquals("foo", $resp->decoded->params->bogus, "Middleware preroute test");
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Middleware test: preroute attachment of bogus param should have passed.');
    }  	

    try {
      $resp = $this->rest_client->http_get('middlewaretest/777');
      $this->assertEquals("2.0", $resp->decoded->version, "Middleware prerender test");
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Middleware test: prerender replacement of version should have passed.');
    }  	
    
  }

  public function test_scoped_middleware() {

    try {
      $resp = $this->rest_client->http_get('foo');
      $this->assertEquals('MODIFIED', $resp->decoded);
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Middleware test: bad return code');
    }

    try {
      $resp = $this->rest_client->http_put('foo');
      $this->assertEquals('MODIFIED', $resp->decoded);
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Middleware test: bad return code');
    }

    try {
      $resp = $this->rest_client->http_get('foo/bar');
      $this->assertEquals('GET', $resp->decoded->method);
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Middleware test: bad return code');
    }

    try {
      $resp = $this->rest_client->http_put('foo/bar');
      $this->assertEquals('MODIFIED', $resp->decoded);
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Middleware test: bad return code');
    }

  }

  public function test_middleware_autodoc() {
    try {
      $resp = $this->rest_client->http_get('testapidocs');
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Middleware test: auto documentator response test should have passed.');
    }  	        
  }

  /** @TODO implement CORS tests. This is currently a little tricky because default
  * callback does not expose response headers **/
  public function test_middleware_cors() {
  }

}