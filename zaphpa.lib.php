<?php

/** Invalid path exception **/
class Zaphpa_InvalidPathException extends Exception {}
/** Non existant middleware class **/
class Zaphpa_InvalidMiddlewareClass extends Exception {}
/** File not found exception **/
class Zaphpa_CallbackFileNotFoundException extends Exception {}
/** Invalid callback exception **/
class Zaphpa_InvalidCallbackException extends Exception {}
/** Invalid URI Parameter exception **/
class Zaphpa_InvalidURIParameterException extends Exception {}
/** Invalid State of HTTP Response exception **/
class Zaphpa_InvalidResponseStateException extends Exception {}
/** Invalid HTTP Response Code exception **/
class Zaphpa_InvalidResponseCodeException extends Exception {}

/* Enable auto-loading of plugins */

require_once(__DIR__ . '/autoloader.php');


/**
* Handy regexp patterns for common types of URI parameters.
* @see: http://zaphpa.github.com/zaphpa/#Pre-defined_Validator_Types
*/
final class Zaphpa_Constants {
  const PATTERN_ARGS       = '?(?P<%s>(?:/.+)+)';
  const PATTERN_ARGS_ALPHA = '?(?P<%s>(?:/[-\w]+)+)';
  const PATTERN_WILD_CARD  = '(?P<%s>.*)';
  const PATTERN_ANY        = '(?P<%s>(?:/?[^/]*))';
  const PATTERN_ALPHA      = '(?P<%s>(?:/?[-\w]+))';
  const PATTERN_NUM        = '(?P<%s>\d+)';
  const PATTERN_DIGIT      = '(?P<%s>\d+)';
  const PATTERN_YEAR       = '(?P<%s>\d{4})';
  const PATTERN_MONTH      = '(?P<%s>\d{1,2})';
  const PATTERN_DAY        = '(?P<%s>\d{1,2})';
  const PATTERN_MD5        = '(?P<%s>[a-z0-9]{32})';  
}

/**
* Callback class for route-processing.
*/
class Zaphpa_Callback_Util {
  
  private static function loadFile($file) {
    if (file_exists($file)) {
      if (!in_array($file, get_included_files())) {
        include($file);
      }
    } else {
      throw new Zaphpa_CallbackFileNotFoundException('Controller file not found');
    }
  }
  
  public static function getCallback($callback, $file = null) {
  
    try {
    
      if ($file) {
        self::loadFile($file);
      }
      
      if (is_array($callback)) {
          
        $method = new ReflectionMethod(array_shift($callback), array_shift($callback));
        
        if ($method->isPublic()) {
          if ($method->isStatic()) {
            $callback = array($method->class, $method->name);
          } else {
            $callback = array(new $method->class, $method->name);
          }
        }
         
      } else if (is_string($callback)) {
        $callback = $callback;
      }
      
      if (is_callable($callback)) {
        return $callback;
      }

      throw new Zaphpa_InvalidCallbackException("Invalid callback");
      
    } catch (Exception $ex) {
      throw $ex;
    }
    
  }
  
}

/**
 * Generic URI matcher and parser implementation.
 */
class Zaphpa_Template {
  
  private static $globalQueryParams = array();
  private $patterns = array();
  
  private $template  = null;
  private $params    = array();
  private $callbacks = array();
  
  public function __construct($path) {
    if ($path{0} != '/') {
      $path = '/'. $path;
    }
    $this->template = rtrim($path, '\/');
  }
  
  public function getTemplate() {
    return $this->template;
  }
  
  public function getExpression() {
    $expression = $this->template;

    if (preg_match_all('~(?P<match>\{(?P<name>.+?)\})~', $expression, $matches)) {
      $expressions = array_map(array($this, 'pattern'), $matches['name']);
      $expression  = str_replace($matches['match'], $expressions, $expression);
    }
    
    return sprintf('~^%s$~', $expression);
  }
  
  public function pattern($token, $pattern = null) {

    if ($pattern) {
      if (!isset($this->patterns[$token])) {
        $this->patterns[$token] = $pattern;
      } 
    } else {
      
      if (isset($this->patterns[$token])) {
        $pattern = $this->patterns[$token];
      } else {
        $pattern = Zaphpa_Constants::PATTERN_ANY;
      }
      
      if ((is_string($pattern) && is_callable($pattern)) || is_array($pattern)) {
        $this->callbacks[$token] = $pattern;
        $this->patterns[$token] = $pattern = Zaphpa_Constants::PATTERN_ANY;
      }

      return sprintf($pattern, $token);
    }
  }
  
  public function addQueryParam($name, $pattern = '', $defaultValue = null) {
    if (!$pattern) {
      $pattern = Zaphpa_Constants::PATTERN_ANY;
    }
    $this->params[$name] = (object) array(
      'pattern' => sprintf($pattern, $name),
      'value'   => $defaultValue
    );
  }
  
  public static function addGlobalQueryParam($name, $pattern = '', $defaultValue = null) {
    if (!$pattern) {
      $pattern = Zaphpa_Constants::PATTERN_ANY;
    }
    self::$globalQueryParams[$name] = (object) array(
      'pattern' => sprintf($pattern, $name),
      'value'   => $defaultValue
    );
  }
  
  public function match($uri) {
    
    try {
    
      $uri = rtrim($uri, '\/');

      if (preg_match($this->getExpression(), $uri, $matches)) {
        
        foreach($matches as $k=>$v) {
          if (is_numeric($k)) {
            unset($matches[$k]);
          } else {
            
            if (isset($this->callbacks[$k])) {              
              $callback = Zaphpa_Callback_Util::getCallback($this->callbacks[$k]);
              $value    = call_user_func($callback, $v);
              if ($value) {
                $matches[$k] = $value;
              } else {
                throw new Zaphpa_InvalidURIParameterException('Ivalid parameters detected');
              }
            }
            
            if (strpos($v, '/') !== false) {
              $matches[$k] = explode('/', trim($v, '\/'));
            }
          }
        }
  
        $params = array_merge(self::$globalQueryParams, $this->params);
  
        if (!empty($params)) {
          
          $matched = false;
          
          foreach($params as $name=>$param) {
            
            if (!isset($_GET[$name]) && $param->value) {
              $_GET[$name] = $param->value;
              $matched = true;
            } else if ($param->pattern && isset($_GET[$name])) {
              $result = preg_match(sprintf('~^%s$~', $param->pattern), $_GET[$name]);
              if (!$result && $param->value) {
                $_GET[$name] = $param->value;
                $result = true;
              }
              $matched = $result;
            } else {
              $matched = false;
            }          
            
            if ($matched == false) {
              throw new Exception('Request do not match');
            }
            
          }
          
        }
        
        return $matches;
        
      }
      
    } catch(Exception $ex) {
      throw $ex;
    }
    
  }
  
  public static function regex($pattern) {
    return '(?P<%s>' . $pattern . ')';
  }
    
}


/**
* Response class
*/
class Zaphpa_Response {

  /** Ordered chunks of the output buffer **/
  public $chunks = array();
  
  public $code = 200;
  
  private $format;
  private $req;

  /** Public constructor **/
  function __construct($request=null) {
    $this->req = $request;  
  }
  
  /**
  * Add string to output buffer.
  */
  public function add($out) {    
    $this->chunks[]  = $out;    
  }
  
  /**
  * Flush output buffer to http client and end request
  *
  *  @param $code
  *      HTTP response Code. Defaults to 200
  *  @param $format
  *      Output mime type. Defaults to request format
  */
  public function send($code=null, $format=null) {      
    $this->flush($code);
    exit(); //prevent any further output
  }
  
  /**
  * Send output to client without ending the script
  *
  *  @param $code
  *      HTTP response Code. Defaults to 200
  *  @param $format
  *      Output mime type. Defaults to request format
  *
  *  @return current respons eobject, so you can chain method calls on a response object.
  */  
  public function flush($code=null, $format=null) {

    if (!empty($code)) { 
      if (headers_sent()) {
        throw new Zaphpa_InvalidResponseStateException("Response code already sent! " . $this->code); 
      }

      $codes = $this->codes();
      if (array_key_exists($code, $codes)) {
        $resp_text = $codes[$code];
        $protocol = $this->req->protocol;
        $this->code = $code;
      } else {
        throw new Zaphpa_InvalidResponseCodeException("Invalid Response Code: " . $code);
      }
    }
        
    // If no format was set explicitely, use the request format for response.
    if (!empty($format)) { 
      if (headers_sent()) {
        throw new Zaphpa_InvalidResponseStateException("Response format already sent! " . $this->format); 
      }
      $this->setFormat($format);       
    }

    // Set default values (200 and request format) if nothing was set explicitely
    if (empty($this->format)) { $this->format = $this->req->format; }
    if (empty($this->code)) { $this->code = 200; }

    if (!headers_sent()) {
      header("Content-Type: $this->format;", TRUE, $this->code);
    }
    
    /* Call preprocessors on each middleware impl */
    foreach (Zaphpa_Router::$middleware as $m) {
      $m->prerender($this->chunks);
    }
        
    $out = implode("", $this->chunks);
    $this->chunks = array(); // reset
    echo ($out);
    return $this;
  }
  
  /**
  * Set output format. Common aliases like: xml, json, html and txt are supported and
  * automatically converted to proper HTTP content type definitions.
  */
  public function setFormat($format) {
    $aliases = $this->req->common_aliases();
    if (array_key_exists($format, $aliases)) {
      $format = $aliases[$format];
    }    
    $this->format = $format;
  }
  
  public function getFormat() {
    return $this->format;
  }
    
  private function codes() {
    return array(  
      '100' => 'Continue',
      '101' => 'Switching Protocols',
      '200' => 'OK',
      '201' => 'Created',
      '202' => 'Accepted',
      '203' => 'Non-Authoritative Information',
      '204' => 'No Content',
      '205' => 'Reset Content',
      '206' => 'Partial Content',
      '300' => 'Multiple Choices',
      '301' => 'Moved Permanently',
      '302' => 'Found',
      '303' => 'See Other',
      '304' => 'Not Modified',
      '305' => 'Use Proxy',
      '307' => 'Temporary Redirect',      
      '400' => 'Bad Request',
      '401' => 'Unauthorized',
      '402' => 'Payment Required',
      '403' => 'Forbidden',
      '404' => 'Not Found',
      '405' => 'Method Not Allowed',
      '406' => 'Not Acceptable',
      '407' => 'Proxy Authentication Required',
      '408' => 'Request Timeout',
      '409' => 'Conflict',
      '410' => 'Gone',
      '411' => 'Length Required',
      '412' => 'Precondition Failed',
      '413' => 'Request Entity Too Large',
      '414' => 'Request-URI Too Long',
      '415' => 'Unsupported Media Type',
      '416' => 'Requested Range Not Satisfiable',
      '417' => 'Expectation Failed',
      '500' => 'Internal Server Error',
      '501' => 'Not Implemented',
      '502' => 'Bad Gateway',
      '503' => 'Service Unavailable',
      '504' => 'Gateway Timeout',
      '505' => 'HTTP Version Not Supported',    
    );
  }
  
} // end Zaphpa_Request


/**
* HTTP Request class
*/
class Zaphpa_Request {
  public $params;
  public $data;
  public $format;
  public $accepted_formats;
  public $encodings;
  public $charsets;  
  public $languages;  
  public $version;
  public $method;
  public $clientIP;
  public $userAgent;
  public $protocol;
  
  function __construct() {
    $this->method = $_SERVER['REQUEST_METHOD'];
    
    $this->clientIP = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : "";
    $this->clientIP = (empty($this->clientIP) && !empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : "";  
    
    $this->userAgent = empty($_SERVER['HTTP_USER_AGENT']) ? "" : $_SERVER['HTTP_USER_AGENT'];    
    $this->protocol = !empty($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : null;

    $this->parse_special('encodings', 'HTTP_ACCEPT_ENCODING', array('utf-8'));    
    $this->parse_special('charsets', 'HTTP_ACCEPT_CHARSET', array('text/html'));
    $this->parse_special('accepted_formats', 'HTTP_ACCEPT');
    $this->parse_special('languages', 'HTTP_ACCEPT_LANGUAGE', array('en-US'));
    
    switch ($this->method) {
        case "GET":
            $this->data = $_GET;
            break;                
        case "POST":
            $this->data = $_GET + $_POST; //people do use query params with POST
            break;                
        default:
            $contents = file_get_contents("php://input");
            $parsed_contents = null;
            parse_str($contents, $parsed_contents);
            $this->data = $_GET + $parsed_contents; //people do use query params with PUT and DELETE
            $this->data['_RAW_HTTP_DATA'] = $contents;
            break;                
    }       

    // Requested output format, if any. 
    // Format in the URL request string takes priority over the one in HTTP headers, defaults to HTML.
    if (!empty($this->data['format'])) {
      $this->format = $this->data['format'];
      $aliases = $this->common_aliases();
      if (array_key_exists($this->format, $aliases)) {
        $this->format = $aliases[$this->format];
      }
      unset($this->data['format']);
    } elseif (!empty($this->accepted_formats[0])) {  
      $this->format = $this->accepted_formats[0];
      unset ($this->data['format']);      
    }
    
  }
  
  /**
  * Covenience method that checks is data item is empty to avoid notice-level warnings
  *
  *    @param $idx
  *        name o the data variable (either request var or HTTP body var).
  */ 
  public function get_var($idx) {      
    return !empty($this->data[$idx]) ? $this->data[$idx] : null;      
  }
  
  /**
  * Subclass this function if you need a different set!
  */
  public function common_aliases() {
    return array(
      'html' => 'text/html',
      'txt' => 'text/plain',
      'css' => 'text/css',
      'js' => 'application/x-javascript',
      'xml' => 'application/xml', 
      'rss' => 'application/rss+xml',
      'atom' => 'application/atom+xml',
      'json' => 'application/json',   
    );
  }
  
  
  /**
  * Parses some packed $_SERVER variables into more useful arrays.
  */
  private function parse_special($varname, $argname, $default=array()) {
    $this->$varname = $default; 
    if (!empty($_SERVER[$argname])) {
      // parse before the first ";" character
      $truncated = substr($_SERVER[$argname], 0, strpos($_SERVER[$argname], ";", 0));
      $truncated = !empty($truncated) ? $truncated : $_SERVER[$argname];
      $this->$varname = explode(",", $truncated);
    }    
  }
  
  /**
  * Make it easy to indicate common formats by mapping them to handy aliases
  */
  private function common_format_parsing() {
  }
    
} // end Zaphpa_Request

abstract class Zaphpa_Middleware {

  public static $context = array();
  public static $routes = array();
    
  public abstract function preprocess(&$route);
  public abstract function preroute(&$req, &$res);
  public abstract function prerender(&$buffer);
} // end Zaphpa_Middleware

class Zaphpa_Router {
  
  protected $routes  = array();
  public static $middleware = array();
  protected static $methods = array('get', 'post', 'put', 'patch', 'delete', 'head', 'options');
  
  public function __construct()
  {
      // List of all episodes
      $this->addRoute(array(
        'path'	=> '/docs',
      	'get'      => array('ZaphpaDocsCallback', 'generateDocs')
      ));
      
      ZaphpaDocsCallback::addRoutes($this->routes);
  }
  
  /**
  * Add a new route to the configured list of routes
  */
  public function addRoute($params) {
    
    if (!empty($params['path'])) {
      
      $template = new Zaphpa_Template($params['path']);
      
      if (!empty($params['handlers'])) {
        foreach ($params['handlers'] as $key => $pattern) {
           $template->pattern($key, $pattern);
        }
      }
                         
      $methods = array_intersect(self::$methods, array_keys($params));

      foreach ($methods as $method) {
        $this->routes[$method][$params['path']] = array(
          'template' => $template,
          'callback' => $params[$method],
          'file'     => !empty($params['file']) ? $params['file'] : '',
        );
        
        Zaphpa_Middleware::$routes[$method][$params['path']] = $this->routes[$method][$params['path']];
      }
      
    }
    
  }
  
  /**
  *  Add a new middleware to the list of middlewares
  */
  public function attach() {

    $ctx = func_get_args();
    $className = array_shift($ctx);

    if (!is_subclass_of($className,'Zaphpa_Middleware')) {
      throw new Zaphpa_InvalidMiddlewareClass("Middleware class: $className does not exist or is not a sub-class of Zaphpa_Middleware" );
    }
        
    self::$middleware[] = new $className($ctx);

  }
  
  private static function getRequestMethod() {
    return strtolower($_SERVER['REQUEST_METHOD']);
  }
  
  /** 
  * Please note this method is performance-optimized to only return routes for
  * current type of HTTP method 
  */
  private function getRoutes($all = false) {
    $method = self::getRequestMethod();
    $routes = empty($this->routes[$method]) ? array() : $this->routes[$method];
    return $routes;
  }
  
  public function route($uri=null) {
  
    if (empty($uri)) {
      // ad hoc fix for parse_url's irrational dislike of colons
      $tokens = parse_url(str_replace(':', '%3A', $_SERVER['REQUEST_URI']));
      $uri = rawurldecode($tokens['path']);
    }
  
    /* Call preprocessors on each middleware impl */
    foreach (self::$middleware as $m) {
      $m->preprocess($this);
    }
    
    $routes = $this->getRoutes();

    try {

      foreach ($routes as $route) {
        $params = $route['template']->match($uri);
                  
        if (!is_null($params)) {   
//          echo("<pre>");   
//          die(print_r($route['template']->getTemplate(),true));
          Zaphpa_Middleware::$context['pattern'] = $route['template']->getTemplate();
          $callback = Zaphpa_Callback_Util::getCallback($route['callback'], $route['file']);
          return $this->invoke_callback($callback, $params);
        }        
      }
      
      throw new Zaphpa_InvalidPathException('Invalid path');
      
    } catch (Exception $ex) {
      throw $ex;
    }
    
  }
  
  /**
  * Main reason this is a separate function is: in case library users want to change
  * invokation logic, without having to copy/paste rest of the logic in the route() function.
  */
  protected function invoke_callback($callback, $params) {
    $req = new Zaphpa_Request();
    $req->params = $params;         
    $res = new Zaphpa_Response($req);
    
    /* Call preprocessors on each middleware impl */
    foreach (self::$middleware as $m) {
      $m->preroute($req,$res);
    }
    
    return call_user_func($callback, $req, $res);    
  }
  

  
} // end Zaphpa_Router

class ZaphpaDocsCallback
{
    private static $currentRoutes;

    public static function addRoutes(&$routes)
    {
        self::$currentRoutes = &$routes;
    }

    /**
     * 	Print out the documentation for all the declared Zaphpa routes
     */ 
    public function generateDocs($req, $res)
    {
        $res->setFormat("text/html");
        $res->add("<h1>API Documentation:</h1>");
        $gets = self::$currentRoutes['get'];

        $style = "<style>
                .docs li { width: 90% }
                h2 { margin: 5px 0px 5px 2px; padding:0px; background-color: #EFEFEF;}
                p { margin: 3px 0px; padding: 0px; }
              </style>";

        $res->add($style);

        $pattern = "<li>
                  <h2>%i</h2>
                  <p>%d</p>
                  <i> <b>File:</b> %f,<b>Class:</b> %c, <b>Method:</b> %m</i>
               </li>";

        $return = "<ul class='docs'>\n";

        foreach ($gets as $id => $get) {
            
            $reflector = new ReflectionClass($get['callback'][0]);
            $classFilename = $get['file'];
            if (empty($classFilename))
            {
                $classFilename = basename($reflector->getFileName());
            }
            
            $callbackMethod = $reflector->getMethod($get['callback'][1]);
            $methodComments = trim(substr($callbackMethod->getDocComment(), 3, -2));
            
            // remove the first *
            $methodComments = preg_replace("/\*/", "", $methodComments, 1);
            
            // replace all the other *'s with line breaks
            $methodComments = preg_replace("/\*/", "<br />", $methodComments);
            
            $data = array(
        '%i' => $id,
        '%f' => $classFilename,
        '%d' => $methodComments,
        '%c' => $get['callback'][0],
        '%m' => $get['callback'][1]
            );

            $return .= strtr($pattern, $data);
        }

        $return .= "</ul>";

        $res->add($return);
        $res->send(200);
    }
}
