<?php

class ZaphpaCORS extends Zaphpa_Middleware {

  private $domain;
  
  function __construct($domain = '*') {
    $this->domain = $domain;
  }
  
  function preroute(&$req, &$res) { 
    header("Access-Control-Allow-Origin: {$this->domain}", true);
    
    if (strcasecmp(Zaphpa_Router::getRequestMethod(), "options") == 0) {
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH", true);
        header("Access-Control-Allow-Headers: origin, x-http-method-override, accept, content-type, authorization", true);
    }
    
  } 
}
