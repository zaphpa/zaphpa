<?php

require_once(dirname(__FILE__) .'/../zaphpa.lib.php');

$router = new Zaphpa_Router();

$router->addRoute(array(
      'path'     => '/pages/{id}/{categories}/{name}/{year}',
      'handlers' => array(
        'id'         => Zaphpa_Constants::PATTERN_DIGIT, //regex
        'categories' => Zaphpa_Constants::PATTERN_ARGS,  //regex
        'name'       => Zaphpa_Constants::PATTERN_ANY,   //regex
        'year'       => 'handle_year',       //callback function
      ),
      'get'      => array('MyController', 'getPage'),
      'post'     => array('MyController', 'postPage'),
      'file'     => 'controllers/mycontroller.php'
    )
);

$router->route();

function handle_year($param) {
  return preg_match('~^\d{4}$~', $param) ? array(
    'ohyesdd' => $param,
    'ba' => 'booooo',
  ) : null;
}
