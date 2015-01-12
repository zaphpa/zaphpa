<?php

namespace Zaphpa\Middleware;

class MethodOverride extends Middleware {
  
  function preprocess(&$router) {
    if (!empty($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']) &&
        Router::getRequestMethod() == "post") {
      $_SERVER['REQUEST_METHOD'] = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
    }
  }
}