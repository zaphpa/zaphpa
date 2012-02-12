<?php

require_once(dirname(__FILE__) . '/../zaphpa.lib.php');
require_once(dirname(__FILE__) . '/TestController.class.php');

$router = new Zaphpa_Router();

$router->addRoute(array(
      'path'     => '/users/{id}',
      'handlers' => array(
        'id'         => Zaphpa_Constants::PATTERN_DIGIT, //regex
      ),
      'get'      => array('TestController', 'getUser'),
    )
);

$router->route();

