<?php

/** Invalid path exception **/
class Zaphpa_InvalidPathException extends Exception {}
/** File not found exception **/
class Zaphpa_CallbackFileNotFoundException extends Exception {}
/** Invalid callback exception **/
class Zaphpa_InvalidCallbackException extends Exception {}
/** Invalid URI Parameter exception **/
class Zaphpa_InvalidURIParameterException extends Exception {}
/** Invalid HTTP Response Code exception **/
class Zaphpa_InvalidResponseCodeException extends Exception {}


/**
* Handy regexp patterns for common types of URI parameters.
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

class Zaphpa_Template {
  
  private static $globalQueryParams = array();
  
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
    static $patterns = array();
    
    if ($pattern) {
      if (!isset($patterns[$token])) {
        $patterns[$token] = $pattern;
      } 
    } else {
      
      if (isset($patterns[$token])) {
        $pattern = $patterns[$token];
      } else {
        $pattern = self::PATTERN_ANY;
      }
      
      if ((is_string($pattern) && is_callable($pattern)) || is_array($pattern)) {
        $this->callbacks[$token] = $pattern;
        $patterns[$token] = $pattern = self::PATTERN_ANY;
      }
      
      return sprintf($pattern, $token);
       
    }    
  }
  
  public function addQueryParam($name, $pattern = '', $defaultValue = null) {
    if (!$pattern) {
      $pattern = self::PATTERN_ANY;
    }
    $this->params[$name] = (object) array(
      'pattern' => sprintf($pattern, $name),
      'value'   => $defaultValue
    );
  }
  
  public static function addGlobalQueryParam($name, $pattern = '', $defaultValue = null) {
    if (!$pattern) {
      $pattern = self::PATTERN_ANY;
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
              $callback = Zaphpa_Callback::getCallback($this->callbacks[$k]);
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
  
  public $code;
  
  private $format;
  private $req;

  function __construct($request=null) {
    $this->req = $request;  
  }
  
  /**
  * Send output to the client
  */
  public function add($out) {    
    $this->chunks[]  = $out;    
  }
  
  /**
  * Send output to client and end request
  *
  *  @param $code
  *      HTTP response Code
  */
  public function send($code=null) {      
    $this->flush($code);
    exit(); //prevent any further output
  }
  
  /**
  * Send output to client without ending the script
  *
  *  @param $code
  *      HTTP response Code
  */  
  public function flush($code=null) {
    $code = (!empty($this->code) && empty($code)) ? $this->code : $code;
    $code = empty($code) ? 200 : $code; // default value if never set
  
    $codes = $this->codes();
    if (array_key_exists($code, $codes)) {
      $resp_text = $codes[$code];
      $protocol = $this->req->protocol;
      header("$protocol $code $resp_text");
    } else {
      throw new Zaphpa_InvalidResponseCodeException("Invalid Response Code: " . $code);
    }
    
    // If no format was set explicitely, use the request format for response.
    $format = !empty($this->format) ? $this->format : $this->req->format;
    header("Content-Type: $format;");    
    
    $out = implode("", $this->chunks);
    echo ($out);
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
            $this->data = $_POST;
            break;                
        default:
            parse_str(file_get_contents("php://input"), $this->data);
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
      'xml' => 'application/xml', 
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


class Zaphpa_Router {
  
  protected $routes  = array();
  protected static $methods = array('get', 'post', 'put', 'delete', 'head', 'options');
  
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
      }
      
    }
    
  }
  
  private static function getRequestMethod() {
    return strtolower($_SERVER['REQUEST_METHOD']);
  }
  
  private function getRoutes() {
    $method = self::getRequestMethod();
    $routes = empty($this->routes[$method]) ? array() : $this->routes[$method];
    return $routes;
  }
  
  public function route($uri=null) {
  
    if (empty($uri)) {
      $tokens = parse_url($_SERVER['REQUEST_URI']);
      $uri = $tokens['path'];
    }
  
    $routes = $this->getRoutes();
        
    try {
    
      foreach ($routes as $route) {
        
        $params = $route['template']->match($uri);
  
        if (!is_null($params)) {
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
    
    return call_user_func($callback, $req, $res);    
  }
  

  
} // end Zaphpa_Router

