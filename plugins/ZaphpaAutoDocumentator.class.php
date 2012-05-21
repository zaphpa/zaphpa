<?php

class ZaphpaAutoDocumentator extends Zaphpa_Middleware {

  private $path = "";
  
  function __construct($path = null) {
    if (empty($path)) {
      $path = array('/docs');
    }
    $this->path = $path[0];
  }
  
  function preprocess(&$router) {
    $router->addRoute(array(
          'path'     => $this->path,
          'get'      => array('ZaphpaAutoDocumentator', 'generateDocs'),
    ));
  }
      
  /**
  * 
  */    
  public function generateDocs($req, $res) {
    $res->setFormat("text/html");

    $res->add("<h1>API Documentation:</h1>");
    
    $style = <<<STYLE
        <style>
          .docs li { width: 90% }
          h2 { margin: 5px 0px 5px 2px; padding:0px; background-color: #EFEFEF;}
          p { margin: 3px 0px; padding: 0px; }
        </style>
STYLE;
    
    $res->add($style);
    
    $pattern = "<li>
              <h2>%i</h2>
              <p>%d</p>
              <i> <b>File:</b> %f, <b>Class:</b> %c, <b>Method:</b> %m</i>
           </li>";
    
    $res->add("<ul class='docs'>\n");
    
    
    $sorted_routes = self::$routes;
    ksort($sorted_routes);
    
    foreach ($sorted_routes as $method => $mroutes) {
      ksort($mroutes);
      foreach ($mroutes as $id => $route) {
          
          $reflector = new ReflectionClass($route['callback'][0]);
          $classFilename = $route['file'];
          if (empty($classFilename))
          {
              $classFilename = basename($reflector->getFileName());
          }
          
          $callbackMethod = $reflector->getMethod($route['callback'][1]);
          $methodComments = trim(substr($callbackMethod->getDocComment(), 3, -2));
          
          // remove the first *
          $methodComments = preg_replace("/\*/", "", $methodComments, 1);
          
          // replace all the other *'s with line breaks
          $methodComments = preg_replace("/\*/", "<br />", $methodComments);
          
          $data = array(
            '%i' => ucfirst($method) . ' ' . $id,
            '%f' => $classFilename,
            '%d' => $methodComments,
            '%c' => $route['callback'][0],
            '%m' => $route['callback'][1]
          );
          
          if (strpos($methodComments, '@hidden') === false) { 
            $res->add( strtr($pattern, $data) );
          }
      }
    }
    
    $res->add("</ul>");    
    $res->send(200);

  }
}