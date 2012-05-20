<?php

require_once(__DIR__ . '/../zaphpa.lib.php');
require_once(__DIR__ . '/TestController.class.php');
require_once(__DIR__ . '/ZaphpaTestMiddleware.class.php');

$router = new Zaphpa_Router();

$router->attach('ZaphpaTestMiddleware');

// This one is a core plugin and should auto-load
$router->attach('ZaphpaAutoDocumentator', '/testapidocs');

$router->addRoute(array(
      'path'     => '/users',
      'get'      => array('TestController', 'getTestJsonResponse'),
    )
);

$router->addRoute(array(
      'path'     => '/users/{id}',
      'handlers' => array(
        'id'         => Zaphpa_Constants::PATTERN_DIGIT,
      ),
      'get'      => array('TestController', 'getTestJsonResponse'),
      'post'      => array('TestController', 'getTestJsonResponse'),
    )
);

$router->addRoute(array(
      'path'     => '/tags/{id}',
      'handlers' => array(
        'id'         => Zaphpa_Constants::PATTERN_ALPHA,
      ),
      'get'      => array('TestController', 'getTestJsonResponse'),
    )
);

$router->addRoute(array(
    'path'     => '/users/{user_id}/books/{book_id}',
    'handlers' => array(
      'user_id'         => Zaphpa_Constants::PATTERN_NUM,
      'book_id'         => Zaphpa_Constants::PATTERN_ALPHA,
    ),
    'get'      => array('TestController', 'getTestJsonResponse'),
  )
);


try {
    $router->route();
} catch (Zaphpa_InvalidPathException $ex) {
    header("Content-Type: application/json;", TRUE, 404);
    $out = array("error" => "not found");        
    die(json_encode($out));
}

