<?php

spl_autoload_register ('zaphpa_autoloader');

function zaphpa_autoloader($classname) {
  $pathname = __DIR__ . '/plugins/' . $classname . '.class.php';
  require_once($pathname);   
}