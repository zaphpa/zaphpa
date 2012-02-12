<?php

/**
 * Abstract parent for Zaphpa test classes.
 */
abstract class ZaphpaTestCase extends PHPUnit_Framework_TestCase {

  protected $server;

  public function setUp() {
    $this->server  = isset($GLOBALS['server'])  ? $GLOBALS['server']  : 'http://127.0.0.1:8080';
  }

}
