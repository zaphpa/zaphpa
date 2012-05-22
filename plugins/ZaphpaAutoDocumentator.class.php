<?php

class ZaphpaAutoDocumentator extends Zaphpa_Middleware {

    private $path = "/docs";

    /**
     * Please note that ZaphpaAutoDocumentation is instantiated twice:
     * once as a middleware, another time: as callback
     */
    private static $details;

    function __construct($ctx = null) {

        if (empty($ctx) || empty($ctx[0])) {
            $this->path = '/docs';
        } else {
            $this->path = $ctx[0];
        }

        if (empty(self::$details) && (empty($ctx) || empty($ctx[1]))) {
            self::$details = 'no';
        } else {
            if (self::$details != 'no') { // make sure it ain't already set
                self::$details = 'yes';
            }
        }
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

        if (self::$details == 'no') {
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