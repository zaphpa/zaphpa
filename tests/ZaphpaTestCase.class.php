<?php

/**
 * Abstract parent for Zaphpa test classes.
 */
abstract class ZaphpaTestCase extends PHPUnit_Framework_TestCase {

  protected $server;
  protected $request;

  public function setUp() {
    $this->server_url  = isset($_ENV['server_url'])  ? $_ENV['server_url']  : 'http://127.0.0.1:5555';

    $this->request = new \Restagent\Request($this->server_url);
  }

}
