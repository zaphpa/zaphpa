<?php

/**
 * Abstract parent for Zaphpa test classes.
 */
abstract class ZaphpaTestCase extends \PHPUnit\Framework\TestCase {

  protected $server;
  protected $server_url;
  protected $request;

  public function setUp(): void {
    $this->server_url  = isset($_ENV['server_url'])  ? $_ENV['server_url']  : 'http://127.0.0.1:5454';

    $this->request = new \Restagent\Request($this->server_url);
  }

}
