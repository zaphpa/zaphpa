<?php

class ZaphpaTestScopedMiddleware extends Zaphpa_Middleware {
  function preprocess(&$router) {
    $router->addRoute(array(
      'path' => '/foo',
      'get'    => array('TestController', 'getTestJsonResponse'),
      'post'   => array('TestController', 'getTestJsonResponse'),
      'put'    => array('TestController', 'getTestJsonResponse'),
      'delete' => array('TestController', 'getTestJsonResponse'),
    ));

    $router->addRoute(array(
      'path'   => '/foo/bar',
      'get'    => array('TestController', 'getTestJsonResponse'),
      'post'   => array('TestController', 'getTestJsonResponse'),
      'put'    => array('TestController', 'getTestJsonResponse'),
      'delete' => array('TestController', 'getTestJsonResponse'),
    ));
  }
  
  function prerender(&$buffer) {
    $buffer = array(json_encode('MODIFIED'));
  }
}