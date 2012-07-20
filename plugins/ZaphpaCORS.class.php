<?php

class ZaphpaCORS extends Zaphpa_Middleware {

  private $domain;
  private $allowedRoutes;
  
  function __construct($ctx = null) {
    if (empty($ctx) || empty($ctx[0])) {
      $this->domain = '*';
    } else {
      $this->domain = $ctx[0];
    }
  }
  
  function preroute(&$req, &$res) { 
    header("Access-Control-Allow-Origin: " . $this->domain , TRUE);
  }
}