<?php

class ZaphpaCORS extends Zaphpa_Middleware {

  private $domain;
  
  function __construct($domain = '*') {
    $this->domain = $domain;
  }
  
  function preroute(&$req, &$res) { 
    header("Access-Control-Allow-Origin: {$this->domain}", true);
  } 
}