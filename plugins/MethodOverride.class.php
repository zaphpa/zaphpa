<?php

class MethodOverride extends Zaphpa_Middleware {  
  
  function preprocess(&$router) {
    if (!empty($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) &&
        Zaphpa_Router::getRequestMethod() == "post") {
      $_SERVER['REQUEST_METHOD'] = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
    }
  }
}