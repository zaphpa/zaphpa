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
* @see: http://zaphpa.org/doc.html#Pre-defined_Validator_Types
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
      include_once($file);
    } else {
      throw new Zaphpa_CallbackFileNotFoundException('Controller file not found');
    }

  }
  
  public static function getCallback($callback, $file = null) {
  
    if ($file) {
      self::loadFile($file);
    }
	
    if (is_array($callback)) {
	
	  $originalClass = array_shift($callback);
	  
      $method = new ReflectionMethod($originalClass, array_shift($callback));
      
      if ($method->isPublic()) {
        if ($method->isStatic()) {
          $callback = array($originalClass, $method->name);
        } else {
          $callback = array(new $originalClass, $method->name);
        }
      }
    }
    
    if (is_callable($callback)) {
      return $callback;
    }

    throw new Zaphpa_InvalidCallbackException('Invalid callback');

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
      $path = "/$path";
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
        
        foreach($matches as $k => $v) {
          if (is_numeric($k)) {
            unset($matches[$k]);
          } else {
            
            if (isset($this->callbacks[$k])) {
              $callback = Zaphpa_Callback_Util::getCallback($this->callbacks[$k]);
              $value    = call_user_func($callback, $v);
              if ($value) {
                $matches[$k] = $value;
              } else {
                throw new Zaphpa_InvalidURIParameterException('Invalid parameters detected');
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
              throw new Exception('Request does not match');
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
    return "(?P<%s>$pattern)";
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
  private $headers = array();

  /** Public constructor **/
  function __construct($request = null) {
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
  public function send($code = null, $format = null) {
    $this->flush($code, $format);
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
  public function flush($code = null, $format = null) {

    if (!empty($code)) { 
      if (headers_sent()) {
        throw new Zaphpa_InvalidResponseStateException("Response code already sent: {$this->code}");
      }

      $codes = $this->codes();
      if (array_key_exists($code, $codes)) {
        $resp_text = $codes[$code];
        $protocol = $this->req->protocol;
        $this->code = $code;
      } else {
        throw new Zaphpa_InvalidResponseCodeException("Invalid Response Code: $code");
      }
    }
        
    // If no format was set explicitely, use the request format for response.
    if (!empty($format)) { 
      if (headers_sent()) {
        throw new Zaphpa_InvalidResponseStateException("Response format already sent: {$this->format}");
      }
      $this->setFormat($format);
    }

    // Set default values (200 and request format) if nothing was set explicitely
    if (empty($this->format)) { $this->format = $this->req->format; }
    if (empty($this->code)) { $this->code = 200; }

    $this->sendHeaders();
    
    /* Call preprocessors on each middleware impl */
    foreach (Zaphpa_Router::$middleware as $m) {
      if ($m->shouldRun('prerender')) {
        $m->prerender($this->chunks);
      }
    }
        
    $out = implode('', $this->chunks);
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

  /**
  * Send headers to instruct browser not to cache this content
  * See http://stackoverflow.com/a/2068407
  */
  public function disableBrowserCache() {
    $this->headers[] = 'Cache-Control: no-cache, no-store, must-revalidate'; // HTTP 1.1.
    $this->headers[] = 'Pragma: no-cache'; // HTTP 1.0.
    $this->headers[] = 'Expires: Thu, 26 Feb 1970 20:00:00 GMT'; // Proxies.
  }

  /**
  *  Send entire collection of headers if they haven't already been sent
  */
  private function sendHeaders() {
    if (!headers_sent()) {
      foreach ($this->headers as $header) {
        header($header);
      }
      header("Content-Type: $this->format;", true, $this->code);
    }
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
        
    $this->method = Zaphpa_Router::getRequestMethod();    
    
    $this->clientIP = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';
    $this->clientIP = (empty($this->clientIP) && !empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';
    
    $this->userAgent = empty($_SERVER['HTTP_USER_AGENT']) ? '' : $_SERVER['HTTP_USER_AGENT'];
    $this->protocol = !empty($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : null;

    $this->parse_special('encodings', 'HTTP_ACCEPT_ENCODING', array('utf-8'));
    $this->parse_special('charsets', 'HTTP_ACCEPT_CHARSET', array('text/html'));
    $this->parse_special('accepted_formats', 'HTTP_ACCEPT');
    $this->parse_special('languages', 'HTTP_ACCEPT_LANGUAGE', array('en-US'));

    // Caution: this piece of code assumes that both $_GET and $_POST are empty arrays when the request type is not GET or POST
    // This is true for current versions of PHP, but it is PHP so it's subject to change
    switch ($this->method) {
      case "GET":
        $this->data = $_GET;
        break;
      default:
        $contents = file_get_contents('php://input');
        $parsed_contents = null;
        // @TODO: considering $_SERVER['HTTP_CONTENT_TYPE'] == 'application/x-www-form-urlencoded' could help here
        parse_str($contents, $parsed_contents);
        $this->data = $_GET + $_POST + $parsed_contents; // people do use query params with POST, PUT, and DELETE
        $this->data['_RAW_HTTP_DATA'] = $contents;
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
    return (is_array($this->data) && isset($this->data[$idx])) ? $this->data[$idx] : null;
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
      'jsonp' => 'text/javascript',
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
  
} // end Zaphpa_Request

abstract class Zaphpa_Middleware {

  const ALL_METHODS = '*';

  public $scope = array();
  public static $context = array();
  public static $routes = array();
  
  /**
   *  Restrict a middleware hook to certain paths and HTTP methods.
   *  
   *  No actual restriction takes place in this method.
   *  We simply place the $methods array into $this->scope, keyed by its $hook.
   *  
   *  @param string $hook
   *    A middleware hook, expecting either 'preroute' or 'prerender'.
   *  @param array $rules
   *    An associative array of paths and their allowed methods:
   *    - path: A URL route string, the same as are used in $router->addRoute(). 
   *      - methods: An array of HTTP methods that are allowed, or an '*' to match all methods.
   *  
   *  @return Zaphpa_Middlware
   *    The current middleware object, to allow for chaining a la jQuery.
   */
  public function restrict($hook, $methods, $route) {
    $this->scope[$hook][$route] = $methods;
    return $this;
  }

  /**
   *  Determine whether the current route has any route restrictions for this middleware.
   *  
   *  If the middleware has restrictions for a given $hook, we check for the current route.
   *  If the current route is in the list of allowed paths, we check that the 
   *  request method is also allowed. Otherwise, the current route needn't run the $hook.
   *  
   *  @param string $hook
   *    A middleware hook, expecting either 'preroute' or 'prerender'.
   *  
   *  @return bool
   *    Whether the current route should run $hook.
   */
  public function shouldRun($hook) {
    if (isset($this->scope[$hook])) {
      if (array_key_exists(self::$context['pattern'], $this->scope[$hook])) {
        $methods = $this->scope[$hook][self::$context['pattern']];

        if ($methods == self::ALL_METHODS) {
          return true;
        }

        if (!is_array($methods)) {
          return false;
        }

        if (!in_array(self::$context['http_method'], array_map('strtolower', $methods))) {
          return false;
        }
      } else {
        return false;
      }
    }
    return true;
  }

  /** Preprocess. This is where you'd add new routes **/
  public function preprocess(&$router) {}
  /** Preroute. This is where you would aler request, or implement things like: security etc. **/
  public function preroute(&$req, &$res) {}
  /** This is your chance to override output. It can be called multiple times for each ->flush() invocation! **/
  public function prerender(&$buffer) {}
  
} // end Zaphpa_Middleware

class Zaphpa_Router {
  
  protected $routes  = array();
  public static $middleware = array();
  
  /** Allowed HTTP Methods. Restricted to only common ones, for security reasons. **/
  protected static $methods = array('get', 'post', 'put', 'patch', 'delete', 'head', 'options');
  
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

    $args = func_get_args();
    $className = array_shift($args);

    if (!is_subclass_of($className, 'Zaphpa_Middleware')) {
      throw new Zaphpa_InvalidMiddlewareClass("Middleware class: '$className' does not exist or is not a sub-class of Zaphpa_Middleware" );
    }
    
    // convert args array to parameter list
    $rc = new ReflectionClass($className);
    $instance = $rc->newInstanceArgs($args);

    self::$middleware[] = $instance;
    return $instance;

  }
  
  /**
  * Get lower-cased representation of current HTTP Request method
  */
  public static function getRequestMethod() {
    return strtolower($_SERVER['REQUEST_METHOD']);
  }
  
  /** 
  * Please note this method is performance-optimized to only return routes for
  * current type of HTTP method 
  */
  private function getRoutes($all = false) {
    if ($all) {
      return $this->routes;
    }
    
    $method = self::getRequestMethod();
    $routes = empty($this->routes[$method]) ? array() : $this->routes[$method];
    return $routes;
  }
  
  public function route($uri = null) {
    if (empty($uri)) {
      // CAUTION: parse_url does not work reliably with relative URIs, it is intended for fully qualified URLs.
      // Using parse_url with URI can cause bugs like this: https://github.com/zaphpa/zaphpa/issues/13
      // We have URI and we could really use parse_url however, so let's pretend we have a full URL by prepending
      // our URI with a meaningless scheme/domain.
      $tokens = parse_url('http://foo.com' . $_SERVER['REQUEST_URI']);
      $uri = rawurldecode($tokens['path']);
    }
  
    /* Call preprocessors on each middleware impl */
    foreach (self::$middleware as $m) {
      $m->preprocess($this);
    }
    
    $routes = $this->getRoutes();
    
    foreach ($routes as $route) {
      $params = $route['template']->match($uri);
      
      if (!is_null($params)) {
        Zaphpa_Middleware::$context['pattern'] = $route['template']->getTemplate();
        Zaphpa_Middleware::$context['http_method'] = self::getRequestMethod();
        Zaphpa_Middleware::$context['callback'] = $route['callback'];
        
        $callback = Zaphpa_Callback_Util::getCallback($route['callback'], $route['file']);
        return $this->invoke_callback($callback, $params);
      }
    }
    
    if (strcasecmp(Zaphpa_Router::getRequestMethod(), "options") == 0)
    {
        return $this->invoke_options();
    }
    
    throw new Zaphpa_InvalidPathException('Invalid path');
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
      if ($m->shouldRun('preroute')) {
        $m->preroute($req,$res);
      }
    }
    
    return call_user_func($callback, $req, $res);
    
  }
  
  protected function invoke_options() {
      $req = new Zaphpa_Request();
      $res = new Zaphpa_Response($req);
      
      /* Call preprocessors on each middleware impl */
      foreach (self::$middleware as $m) {
          if ($m->shouldRun('preroute')) {
              $m->preroute($req,$res);
          }
      }
      
      $res->setFormat("httpd/unix-directory");
      header("Allow: " . implode(",", array_map('strtoupper',Zaphpa_Router::$methods)));
      $res->send(200);
      
      return true;
      
  }
} // end Zaphpa_Router
