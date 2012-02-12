<?php

require_once(dirname(__FILE__) . '/../zaphpa.lib.php');
require_once(dirname(__FILE__) . '/TestController.class.php');

$router = new Zaphpa_Router();

$router->addRoute(array(
      'path'     => '/users/{id}',
      'handlers' => array(
        'id'         => Zaphpa_Constants::PATTERN_DIGIT, //regex
      ),
      'get'      => array('TestController', 'getUserByNumericId'),
    )
);

$router->addRoute(array(
      'path'     => '/tags/{id}',
      'handlers' => array(
        'id'         => Zaphpa_Constants::PATTERN_ALPHA, //regex
      ),
      'get'      => array('TestController', 'getTagByAlphaId'),
    )
);

try {
    $router->route();
} catch (Zaphpa_InvalidPathException $ex) {
    header("Content-Type: application/json;");    
    header("HTTP/1.0 404 Not Found");
    header("Status: 404 Not Found"); // For FastCGI
    
    $out = array("error" => "not found");        
    $out = json_encode($out);
    die ($out);
}

