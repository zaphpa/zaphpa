<?php

require_once (__DIR__ . '/ZaphpaTestCase.class.php');
require_once (__DIR__ . '/restagent.lib.php');

/**
 * @file
 * Functional tests for basic endpoint processing
 */
class ZaphpaRestTest extends ZaphpaTestCase {
  private $request;
  
  public function setUp() {
    parent::setUp();
    $this->request = new \restagent\Request($this->server_url);
  }

  public function test_pattern_num_single() {
    try {
      $resp = (object) $this->request->get('/users/1');
      $resp->decoded = json_decode($resp->data);
      $this->assertEquals(1, $resp->decoded->params->id, '{1} was not parsed correctly.');
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Request failed when numeric characters should have passed.');
    }

    $resp = $this->request->get('/users/alpha');
    $this->assertEquals(404, $resp['code'], 'Alpha characters were parsed when a numeric arg was expected.');    
  }  
  
  public function test_freeform() {
    try {
      // This test ensures that this bug is fixed: https://github.com/zaphpa/zaphpa/issues/13
      $resp = (object) $this->request->get('/v2/times/2012-10-11T12:00/episodes?apiKey=SOMETHINGTHATLOOKSLIKEMD5');
      $resp->decoded = json_decode($resp->data);
      $this->assertEquals('2012-10-11T12:00', $resp->decoded->params->dt, 'free-form URL parameter was not parsed correctly.');
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Request failed when any characters should have passed.');
    }

    $resp = $this->request->get('/users/alpha');
    $this->assertEquals(404, $resp['code'], 'Alpha characters were parsed when a numeric arg was expected.');    
  }  

  public function test_pattern_alpha_single() {
    try {
      $resp = (object) $this->request->get('/tags/alpha');
      $resp->decoded = json_decode($resp->data);
      
      $this->assertEquals('alpha', $resp->decoded->params->id, '{alpha} was not parsed correctly.');
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Request failed when alpha-num chars should have passed.');
    }

    try {
      $resp = (object) $this->request->get('/tags/234234');
      $resp->decoded = json_decode($resp->data);      
      $this->assertEquals('234234', $resp->decoded->params->id, 'URI param {234234} was not parsed correctly.');
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Request failed when numeric chars should have passed.');
    }

    $resp = (object) $this->request->get('/tags/aa[]&');
    $this->assertEquals('404', $resp->code, 'Special characters were parsed when an alpha-num arg was expected.');
  }

  public function test_num_and_alpha_two_params() {
    try {
      $resp = (object) $this->request->get('/users/1234/books/shakespear');
      $resp->decoded = json_decode($resp->data);      
          
      $this->assertEquals('1234', $resp->decoded->params->user_id);
      $this->assertEquals('shakespear', $resp->decoded->params->book_id);
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Request failed when both args should have succeeded.');
    }

    try {
      $resp = (object) $this->request->get('/users/1234/books/35345');
      $resp->decoded = json_decode($resp->data);      
      
      $this->assertEquals('1234', $resp->decoded->params->user_id);
      $this->assertEquals('35345', $resp->decoded->params->book_id);
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Request failed when both args should have succeeded.');
    }

    $resp = (object) $this->request->get('/users/asfksalfjk/books/35345');  
    $this->assertEquals(404, $resp->code, 'Alpha characters were parsed when a numeric arg was expected');
  }
  
  public function test_middleware() {
    try {      
      $resp = (object) $this->request->get('/middlewaretest/777');
      $resp->decoded = json_decode($resp->data); 
      
      $this->assertEquals(777, $resp->decoded->params->mid, 'Middleware response test');
      $this->assertEquals('foo', $resp->decoded->params->bogus, 'Middleware preroute test');
      $this->assertEquals('2.0', $resp->decoded->version, 'Middleware prerender test');
      
      
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Middleware test: middleware routing test should have passed.');
    }   
  }

  public function test_scoped_middleware() {
    $resp = (object) $this->request->get('/foo');
    $resp->decoded = json_decode($resp->data); 
    
    $this->assertEquals('success', $resp->decoded->scopeModification, 'Scoped middleware test: Expected middleware to run and modify response for GET.');
    
    $resp = (object) $this->request
                          ->data("hobby", "programming")
                          ->put('/foo');
    $resp->decoded = json_decode($resp->data);     
    $this->assertEquals('success', $resp->decoded->scopeModification, 'Scoped middleware test: Expected middleware to run and modify response for PUT.');
    
    $resp = (object) $this->request->get('/foo/bar');
    $resp->decoded = json_decode($resp->data);     
    $this->assertEquals('get', $resp->decoded->method, 'Scoped middleware test: Expected middleware not to run.');

    $resp = (object) $this->request
                          ->data("hobby", "programming")
                          ->put('/foo/bar');
    $resp->decoded = json_decode($resp->data);    
    
    $this->assertEquals('success', $resp->decoded->scopeModification, 'Scoped middleware test: Expected middleware to run and modify response.');    
  }

  public function test_middleware_autodoc() {
    try {
      $resp = (object) $this->request->get('/testapidocs');
      $resp->decoded = json_decode($resp->data);     
      $this->assertEquals(200, $resp->code, 'Middleware test: auto documentator response test should pass.');
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Middleware test: auto documentator response test should have passed.');
    }
  }

  public function test_middleware_cors() {
    $resp = (object) $this->request->get('/users');
    $resp->decoded = json_decode($resp->data);         

    $this->assertArrayHasKey('Access-Control-Allow-Origin', $resp->headers, 'CORS test: expected proper CORS headers to be set.');
    $this->assertEquals('*', $resp->headers['Access-Control-Allow-Origin'], 'CORS test: expected proper CORS headers to be set.');

    $resp = (object) $this->request->get('/users/123');
    $resp->decoded = json_decode($resp->data);             
    $this->assertArrayNotHasKey('Access-Control-Allow-Origin', $resp->headers, 'CORS test: expected CORS headers not to be set.');
  }
  
  public function test_middleware_methodoverride() {
    $resp = (object) $this->request
                          ->header("X-HTTP-Method-Override", "patch")
                          ->post('/users/12345');

    $resp->decoded = json_decode($resp->data);         
    $this->assertEquals('patch', $resp->decoded->method, 'Method Override test: expected http method to be overriden in POST request.');

  }
  
  public function test_get_query_param() {
    $resp = (object) $this->request->get('/query_var_test?test_param=');
    $resp->decoded = json_decode($resp->data);      
    $this->assertEquals('', $resp->decoded->test_param, '$query->get_var() test. Parameter is empty');

    $resp = (object) $this->request->get('/query_var_test');
    $resp->decoded = json_decode($resp->data);      
    $this->assertNull($resp->decoded->test_param, '$query->get_var() test. Parameter is missing');

    $resp = (object) $this->request->get('/query_var_test?test_param=0');
    $resp->decoded = json_decode($resp->data);      
    $this->assertEquals(0, $resp->decoded->test_param, '$query->get_var() test. Parameter is zero');

    $resp = (object) $this->request->get('/query_var_test?test_param=3');
    $resp->decoded = json_decode($resp->data);   
    $this->assertEquals(3, $resp->decoded->test_param, '$query->get_var() test. Parameter has non-zero value');
  }

}