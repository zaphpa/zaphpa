<?php

/**
 * Please note that ZaphpaAutoDocumentation is instantiated twice
 * once as a middleware, another time: as callback
 */
class ZaphpaAutoDocumentator extends Zaphpa_Middleware {
  private $path = '/docs';
  private static $details = false;

  function __construct($path = '/docs', $details = false) {
    $this->path = $path;

    // Don't overwrite a true value if we've got one
    self::$details = (self::$details || $details);
  }

  function preprocess(&$router) {
    $router->addRoute(array(
      'path' => $this->path,
      'get'  => array('ZaphpaAutoDocumentator', 'generateDocs'),
    ));
  }

  public function generateDocs($req, $res) {
    $res->setFormat('html');

    $res->add("<h1>API Documentation:</h1>");

    $style = <<<STYLE
    <style>
      .docs li { width: 90%; }
      h2 { margin: 5px 0 5px 2px; padding: 0; background-color: #EFEFEF;}
      p { margin: 3px 0; padding: 0; }
    </style>
STYLE;

    $res->add($style);

    if (!self::$details) {
      $pattern = "<li>
          <h2>%i</h2>
          <p>%d</p>
         </li>";
    } else {
      $pattern = "<li>
      <h2>%i</h2>
      <p>%d</p>
      <i> <b>File:</b> %f, <b>Class:</b> %c, <b>Method:</b> %m</i>
      </li>";
    }

    $res->add("<ul class='docs'>\n");

    $sorted_routes = self::$routes;
    ksort($sorted_routes);

    foreach ($sorted_routes as $method => $mroutes) {
      ksort($mroutes);
      foreach ($mroutes as $id => $route) {
        $reflector = new ReflectionClass($route['callback'][0]);
        $classFilename = $route['file'];
        if (empty($classFilename)) {
          $classFilename = basename($reflector->getFileName());
        }

        $callbackMethod = $reflector->getMethod($route['callback'][1]);
        $methodComments = trim(substr($callbackMethod->getDocComment(), 3, -2));

        // remove the first *
        $methodComments = preg_replace('/\*/', '', $methodComments, 1);

        // replace all the other *'s with line breaks
        $methodComments = preg_replace('/\*/', '<br />', $methodComments);

        $data = array(
          '%i' => strtoupper($method) . ' ' . $id,
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