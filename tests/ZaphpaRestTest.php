<?php

require_once (__DIR__ . '/ZaphpaTestCase.class.php');
require_once (__DIR__ . '/ZaphpaRestClient.class.php');

/**
 * @file
 * Functional tests for basic endpoint processing
 */
class ZaphpaRestTest extends ZaphpaTestCase {
  private $rest_client;
  
  public function setUp() {
    parent::setUp();
    $this->rest_client = ZaphpaRestClient::get_instance($this->server_url);
  }

  public function test_pattern_num_single() {
    try {
      $resp = $this->rest_client->get('users/1');
      $this->assertEquals(1, $resp->decoded->params->id, '{1} was not parsed correctly.');
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Request failed when numeric characters should have passed.');
    }
    
    try {
      $resp = $this->rest_client->get('users/alpha');
    } catch (ZaphpaRestClientException $ex) {
      return;
    }
     
    $this->fail('Alpha characters were parsed when a numeric arg was expected.');
  }  

  public function test_pattern_alpha_single() {
    try {
      $resp = $this->rest_client->get('tags/alpha');
      $this->assertEquals('alpha', $resp->decoded->params->id, '{alpha} was not parsed correctly.');
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Request failed when alpha-num chars should have passed.');
    }

    try {
      $resp = $this->rest_client->get('tags/234234');
      $this->assertEquals('234234', $resp->decoded->params->id, '{234234} was not parsed correctly.');
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Request failed when alpha-num chars should have passed.');
    }

    try {
      $this->rest_client->get('tags/aa$%`^sff|_');
    } catch (ZaphpaRestClientException $ex) {
      return;
    }
    
    $this->fail('Special characters were parsed when an alpha-num arg was expected.');
  }

  public function test_num_and_alpha_two_params() {
    try {
      $resp = $this->rest_client->get('users/1234/books/shakespear');
      $this->assertEquals('1234', $resp->decoded->params->user_id);
      $this->assertEquals('shakespear', $resp->decoded->params->book_id);
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Request failed when both args should have succeeded.');
    }

    try {
      $resp = $this->rest_client->get('users/1234/books/35345');
      $this->assertEquals('1234', $resp->decoded->params->user_id);
      $this->assertEquals('35345', $resp->decoded->params->book_id);
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Request failed when both args should have succeeded.');
    }

    try {
      $this->rest_client->get('users/asfksalfjk/books/35345');
    } catch (ZaphpaRestClientException $ex) {
      return;
    }

    $this->fail('Alpha characters were parsed when a numeric arg was expected');
  }
  
  public function test_middleware() {
    try {
      $resp = $this->rest_client->get('middlewaretest/777');
      $this->assertEquals(777, $resp->decoded->params->mid, 'Middleware response test');
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Middleware test: response test should have passed.');
    }   
    
    try {
      $resp = $this->rest_client->get('middlewaretest/777');
      $this->assertEquals('foo', $resp->decoded->params->bogus, 'Middleware preroute test');
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Middleware test: preroute attachment of bogus param should have passed.');
    }   

    try {
      $resp = $this->rest_client->get('middlewaretest/777');
      $this->assertEquals('2.0', $resp->decoded->version, 'Middleware prerender test');
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Middleware test: prerender replacement of version should have passed.');
    }   
  }

  public function test_scoped_middleware() {
    $resp = $this->rest_client->get('foo');
    $this->assertEquals('success', $resp->decoded->scopeModification, 'Scoped middleware test: Expected middleware to run and modify response for GET.');
      
    /**
    $resp = $this->rest_client->put('foo');
    $this->assertEquals('success', $resp->decoded->scopeModification, 'Scoped middleware test: Expected middleware to run and modify response for PUT.');
    **/
    
    $resp = $this->rest_client->get('foo/bar');
    $this->assertEquals('GET', $resp->decoded->method, 'Scoped middleware test: Expected middleware not to run.');

    /**
    $resp = $this->rest_client->put('foo/bar');
    $this->assertEquals('success', $resp->decoded->scopeModification, 'Scoped middleware test: Expected middleware to run and modify response.');
    **/
    
  }

  public function test_middleware_autodoc() {
    try {
      $resp = $this->rest_client->get('testapidocs');
    } catch (ZaphpaRestClientException $ex) {
      $this->fail('Middleware test: auto documentator response test should have passed.');
    }
  }

  public function test_middleware_cors() {
    $resp = $this->rest_client->get('users');
    $this->assertArrayHasKey('Access-Control-Allow-Origin', $resp->headers, 'CORS test: expected proper CORS headers to be set.');
    $this->assertEquals('*', $resp->headers['Access-Control-Allow-Origin'], 'CORS test: expected proper CORS headers to be set.');

    $resp = $this->rest_client->get('users/123');
    $this->assertArrayNotHasKey('Access-Control-Allow-Origin', $resp->headers, 'CORS test: expected CORS headers not to be set.');
  }
}