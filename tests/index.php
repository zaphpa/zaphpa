<?php

require_once '../vendor/autoload.php';

require_once(__DIR__ . '/TestController.class.php');
require_once(__DIR__ . '/ZaphpaTestMiddleware.class.php');
require_once(__DIR__ . '/ZaphpaTestScopedMiddleware.class.php');

$router = new \Zaphpa\Router();

$router->attach('\ZaphpaTestMiddleware');
$router->attach('\Zaphpa\Middlewares\ZaphpaAutoDocumentator', '/testapidocs');

$router->attach('\Zaphpa\Middlewares\MethodOverride');

$router
  ->attach('\Zaphpa\Middlewares\ZaphpaCORS', '*')
  ->restrict('preroute', '*', '/users');

$router
  ->attach('\ZaphpaTestScopedMiddleware')
  ->restrict('prerender', '*', '/foo')
  ->restrict('prerender', array('put'), '/foo/bar');

$router->addRoute(array(
  'path' => '/users',
  'get'  => array('\TestController', 'getTestJsonResponse'),
));

$router->addRoute(array(
  'path'     => '/users/{id}',
  'handlers' => array(
    'id'       => \Zaphpa\Constants::PATTERN_DIGIT,
  ),
  'get'      => array('\TestController', 'getTestJsonResponse'),
  'post'     => array('\TestController', 'getTestJsonResponse'),
  'patch'    => array('\TestController', 'getTestJsonResponse'),
));

$router->addRoute(array(
  'path'     => '/v2/times/{dt}/episodes',
  'get'      => array('\TestController', 'getTestJsonResponse'),
));

$router->addRoute(array(
  'path'     => '/tags/{id}',
  'handlers' => array(
    'id'       => \Zaphpa\Constants::PATTERN_ALPHA,
  ),
  'get'      => array('\TestController', 'getTestJsonResponse'),
));

$router->addRoute(array(
  'path'     => '/users/{user_id}/books/{book_id}',
  'handlers' => array(
    'user_id'  => \Zaphpa\Constants::PATTERN_NUM,
    'book_id'  => \Zaphpa\Constants::PATTERN_ALPHA,
  ),
  'get'      => array('\TestController', 'getTestJsonResponse'),
));

$router->addRoute(array(
  'path'     => '/query_var_test',
  'get'      => array('\TestController', 'getQueryVarTestJsonResponse'),
));


try {
  $router->route();
} catch (\Zaphpa\Exceptions\InvalidPathException $ex) {
  header('Content-Type: application/json;', true, 404);
  die(json_encode(array('error' => 'not found')));
}
