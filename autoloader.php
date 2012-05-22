<?php

spl_autoload_register ('zaphpa_autoloader');

function zaphpa_autoloader($classname) {
  static $already_checked = array();
  
  //Marginal perf. optimization: this is faster than running file_exists() + require_once() repeatedly
  if (array_key_exists($classname, $already_checked)) {
    return;
  }
  
  $already_checked[$classname] = true;
  
  $pathname = __DIR__ . '/plugins/' . $classname . '.class.php';
  if (file_exists($pathname)) {    
    require_once($pathname);   
  }
}