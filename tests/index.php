<?php

require_once(__DIR__ . '/../zaphpa.lib.php');
require_once(__DIR__ . '/TestController.class.php');
require_once(__DIR__ . '/ZaphpaTestMiddleware.class.php');
require_once(__DIR__ . '/ZaphpaTestScopedMiddleware.class.php');

$router = new Zaphpa_Router();

$router->attach('ZaphpaTestMiddleware');
$router->attach('ZaphpaAutoDocumentator', '/testapidocs');

$router->attach('MethodOverride');

$router
  ->attach('ZaphpaCORS', '*')
  ->restrict('preroute', '*', '/users');

$router
  ->attach('ZaphpaTestScopedMiddleware')
  ->restrict('prerender', '*', '/foo')
  ->restrict('prerender', array('put'), '/foo/bar');

$router->addRoute(array(
  'path' => '/users',
  'get'  => array('TestController', 'getTestJsonResponse'),
));

$router->addRoute(array(
  'path'     => '/users/{id}',
  'handlers' => array(
    'id'       => Zaphpa_Constants::PATTERN_DIGIT,
  ),
  'get'      => array('TestController', 'getTestJsonResponse'),
  'post'     => array('TestController', 'getTestJsonResponse'),
  'patch'    => array('TestController', 'getTestJsonResponse'),
));

$router->addRoute(array(
  'path'     => '/v2/times/{dt}/episodes',
  'get'      => array('TestController', 'getTestJsonResponse'),
));

$router->addRoute(array(
  'path'     => '/tags/{id}',
  'handlers' => array(
    'id'       => Zaphpa_Constants::PATTERN_ALPHA,
  ),
  'get'      => array('TestController', 'getTestJsonResponse'),
));

$router->addRoute(array(
  'path'     => '/users/{user_id}/books/{book_id}',
  'handlers' => array(
    'user_id'  => Zaphpa_Constants::PATTERN_NUM,
    'book_id'  => Zaphpa_Constants::PATTERN_ALPHA,
  ),
  'get'      => array('TestController', 'getTestJsonResponse'),
));

$router->addRoute(array(
  'path'     => '/query_var_test',
  'get'      => array('TestController', 'getQueryVarTestJsonResponse'),
));


try {
  $router->route();
} catch (Zaphpa_InvalidPathException $ex) {
  header('Content-Type: application/json;', true, 404);
  die(json_encode(array('error' => 'not found')));
}
