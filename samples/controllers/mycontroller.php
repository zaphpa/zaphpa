<?php

class MyController {
	
  public function getPage($req, $res) {
    $res->add("<h1>API Request Input:</h1>");
    $res->add(pre($req));
    $res->send(200);
  }
	
  public function postPage($req, $res) {
    $res->add("<h1>API Request Input:</h1>");
    $res->add(pre($req));
    $res->send(201);
  }
	
}

function pre($o) {
  return strtr('<pre>%s</pre>', 
               array('%s' => print_r($o, true))
         );
}
