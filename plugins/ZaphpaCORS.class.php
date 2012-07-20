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
    
    if (empty($ctx) || empty($ctx[1])) {
      $this->allowedRoutes = array();
    } else {
      $this->allowedRoutes = $ctx[1];
    }
  }
  
  function preroute(&$req, &$res) { 
    if (!empty($this->allowedRoutes) && !in_array(self::$context['pattern'], $this->allowedRoutes)) {
      return;
    }
    
    header("Access-Control-Allow-Origin: " . $this->domain , TRUE);
  }  
}