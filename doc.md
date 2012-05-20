---
layout: default
title: Zaphpa RESTful Microframework
---

## Installation

To start serving RESTful HTTP requests, you need to go through three simple steps:

1. Configure virtualhost in your web-server so that all HTTP requests to "non-existant" "files" are sent to your 
PHP file, say: api.php (see: [Appendix A](/doc.html#Appendix_A_Setting_Up_Zaphpa_Library) )
1. Create api.php where you instantiate and configure a router object
1. Write controller callbacks.

## A Simple Router

For a very simple case of getting specific user object, the code of api.php would look something like:

    require_once(__DIR__ . '/zaphpa/zaphpa.lib.php');
    $router = new Zaphpa_Router();
    
    $router->addRoute(array(
      'path'     => '/users/{id}',
      'handlers' => array(
        'id'         => Zaphpa_Constants::PATTERN_DIGIT, //enforced to be numeric
      ),
      'get'      => array('MyController', 'getPage'),
    ));
    
    try {
      $router->route();
    } catch (Zaphpa_InvalidPathException $ex) {      
      header("Content-Type: application/json;", TRUE, 404);
      $out = array("error" => "not found");        
      die(json_encode($out));
    }     

In this example, {id} is a URI parameter of the type "digit", so `MyController->getPage()` function will get control to serve URLs like:

* http://example.com/users/32424
* http://example.com/users/23

However, we asked the library to ascertain that the {id} parameter is a number by attaching a validating 
handler: "Zaphpa_Constants::PATTERN_DIGIT" to it. As such, following URLs will not be handed over to 
the `MyController->getPage()` callback:

* http://example.com/users/ertla
* http://example.com/users/asda32424
* http://example.com/users/32424sfsd
* http://example.com/users/324sdf24

## Simple Callbacks

A callback can be a simple PHP function. In most cases it will probably be a method on a class. Callbacks are passed two arguments:

1. `$req` is an object created and populated by Zaphpa from current HTTP request. 
1. `$res` is a response object. It is used by your callback code to incrementally assemble a response, including both the response 
headers, as well as: the response body. 

We will look into the details of $req and $res objects further in the documentation. Following are some example callback implementations:

    class MyController {
    
      public function getPage($req, $res) {
        $res->setFormat("json");
        $res->add(json_encode($req->params));
        $res->add(json_encode($req->data));
        $res->send(301);    
      }
    
      public function postPage($req, $res) {
      	$res->add(json_encode($req->params));
        $res->add(json_encode($req->data));
        $res->send(201, 'json');    
      }
    
    }	

## Request Object

`$req (request)` object contains data parsed from the request, and can include properties like:

1. `$params` - which contains all the placeholders matched in the URL (e.g. the value of the "id" argument)
1. `$data`  - an array that contains HTTP data. In case of HTTP GET it is: parsed request parameters, for HTTP POST, PUT and DELETE requests: data variables contained in the HTTP Body of the request.
1. `$version` - version of the API if one is versioned (not yet implemented)
1. `$format` - data format that was requested (e.g. XML, JSON etc.)
	
Following is an example request object:

    Zaphpa_Request Object
    (
      [params] => Array
        (
          [id] => 234234
        )
      [data] => Array
        (
          [api] => 46546456456
        )
      [formats] => Array
        (
          [0] => text/html
          [1] => application/xhtml+xml
          [2] => application/xml
        )
      [encodings] => Array
        (
          [0] => gzip
          [1] => deflate
          [2] => sdch
        )
      [charsets] => Array
        (
          [0] => ISO-8859-1
          [1] => utf-8
        )
      [languages] => Array
        (
          [0] => en-US
          [1] => en
        )
      [version] => 
      [method] => GET
      [clientIP] => 172.30.25.142
      [userAgent] => Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/535.2...
      [protocol] => HTTP/1.1
    )

### Request parsing

Zaphpa provides multiple shortcuts to make request-parsing easier and less error-prone. One such shortcut is `$req->get_var(<varname>)` function.

1. `$req->get_var('varname')` - since $req object populates `$data` object, you can access request variables 
(query variables for GET or HTTP Body data for PUT/POST/DELETE) through the $data array directly. However am HTTP client may not set a variable 
that your callback expects, causing PHP to throw a warning. Instead of having your callback code check each call to `$req->data['varname']` on 
being empty Zaphpa provides a convenience method: $req->get_var('varanme'). get_var() returns value of the HTTP variable if it is set, 
or null, otherwise.

## Response Object

`$res (response)` object is used to incrementally create content. You can add chunks of text to the output buffer 
by calling: `$res->add (String)` and once you are done you can send entire buffer to the HTTP client by issuing: 
`$res->send(<HTTP_RESPONSE_CODE>)`. HTTP_RESPONSE_CODE is an optional parameter which defaults to (you guessed it:) 200.

Response object is basically an output buffer that you can keep adding output chunks to, while you are building a response. 
Following methods are available on the response class:

1. `$res->add($string)` - adds a string to output buffer
1. `$res->flush($code, $format)` - sends current output buffer to client. Can take optional output code (defaults to 200) and output format 
   (defaults to request format) arguments. 
   **Caution**: obviously you can/should only indicate $code or $format, the first time
   you invoke the method, since these values can not be set once output is sent to the client.
1. `$res->send($code, $format)` - sends current output buffer to the client and terminates response.


## Output format aliases

The $format argument of the send() and flush() should be passed as a standard mime-type string. However, for convenience and brevity Zaphpa
allows indicating some simple aliases for common mime types:

    'html' => 'text/html',
    'txt' => 'text/plain',
    'css' => 'text/css',
    'js' => 'application/x-javascript',
    'xml' => 'application/xml', 
    'rss' => 'application/rss+xml',
    'atom' => 'application/atom+xml',
    'json' => 'application/json',

## A More Advanced Router Example

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
    ));
    
    // Add default 404 handler.
    try {
      $router->route();
    } catch (Zaphpa_InvalidPathException $ex) {
      header("Content-Type: application/json;", TRUE, 404);
      $out = array("error" => "not found");        
      die(json_encode($out));
    }
    
    function handle_year($param) {
      return preg_match('~^\d{4}$~', $param) ? array(
        'ohyesdd' => $param,
        'ba' => 'booooo',
      ) : null;
    }

Please note the "file" parameter to the `->addRouye()` call. This parameter indicates file where MyController class should be loaded from,
if you do not already have the corresponding class loaded (through an auto-loader or explicit require() call).

## Routing to Entities

So far we have discussed routing individual URI patterns. However, when building a RESTful API, you often need to create 
full Resources or Endpoints - API lingo for objects that can be managed in a full: Create, Read, Update, Delete (CRUD) lifecycle.

One way you can do this is to fully declare all four routes. But that would mean a lot of duplicated configuration. 
We hate code duplication, so here's a nifty shortcut you can use:

    $router->addRoute(array(
      'path'     => '/books/{id}',
      'handlers' => array(
        'id'         => Zaphpa_Constants::PATTERN_DIGIT, 
      ),
      'get'      => array('BookController', 'getBook'),
      'post'     => array('BookController', 'createBook'),
      'put'      => array('BookController', 'updateBook'),
      'delete'     => array('BookController', 'deleteBook'),
      'file'     => 'controllers/bookcontroller.php'
    ));

## Pre-defined Validator Types

Zaphpa allows indicating completely custom function callbacks as validating handlers, but for convenience it also 
provides number of pre-defined, common validators:

    const PATTERN_NUM        = '(?P<%s>\d+)';
    const PATTERN_DIGIT      = '(?P<%s>\d+)';
    const PATTERN_MD5        = '(?P<%s>[a-z0-9]{32})';
    const PATTERN_ALPHA      = '(?P<%s>(?:/?[-\w]+))';
    const PATTERN_ARGS       = '?(?P<%s>(?:/.+)+)';
    const PATTERN_ARGS_ALPHA = '?(?P<%s>(?:/[-\w]+)+)';
    const PATTERN_ANY        = '(?P<%s>(?:/?[^/]*))';
    const PATTERN_WILD_CARD  = '(?P<%s>.*)'; 
    const PATTERN_YEAR       = '(?P<%s>\d{4})';
    const PATTERN_MONTH      = '(?P<%s>\d{1,2})';
    const PATTERN_DAY        = '(?P<%s>\d{1,2})';

You may be able to guess the functionality from the regexp patterns associated with each pre-defined validator, but let's 
go through the expected behavior of each one of them:

* `PATTERN_NUM` - ensures a path element to be numeric
* `PATTERN\_DIGIT` - alias to `PATTERN\_NUM` 
* `PATTERN\_MD5` - ensures a path element to be valid MD5 hash
* `PATTERN\_ALPHA` - ensures a path element to be valid alpha-numeric string (i.e. latin characters and numbers, as defined 
by \w pattern of regular expression syntax).
* `PATTERN\_ARGS` - is a more sophisticated case that takes some explanation. It tries to match multiple path elements and 
could be useful in URLs like: 
    * `/news/212424/**us/politics/elections**/some-title-goes-here/2012` 
where "us/politics/elections" is a part with variable number of "categories". To parse such URL you could define a validator 
like: <script src="https://gist.github.com/1900311.js?file=gistfile1.txt"></script> and you would get the function arguments 
in the callback as: <script src="https://gist.github.com/1900324.js?file=gistfile1.txt"></script>
* `PATTERN\_ARGS\_ALPHA` - acts the exact same way as PATTERN\_ARGS but limits character set to alpha-numeric ones.
* `PATTERN\_ANY` (default) - matches any one argument
* `PATTERN\_WILD\_CARD` - "greedy" version of PATTERN\_ANY that can match multiple arguments
* `PATTERN\_YEAR` - matches a 4-digit representation of a year.
* `PATTERN\_MONTH` - matches 1 or 2 digit representation of a month
* `PATTERN\_DAY` - matches 1 or 2 digit representation of a numeric day.

For more custom cases, you can use a custom regex:
<script src="https://gist.github.com/1900357.js?file=gistfile1.txt"></script>

or attach a validator/parser callback function where you can do whatever you need: 
<script src="https://gist.github.com/1900339.js?file=gistfile1.txt"></script>

The output of a custom parser callback should match that of a regex call i.e.: should return a parsed array of matches or a null value.

## Appendix A: Setting Up Zaphpa Library

You need to register a PHP script to handle all HTTP requests. For Apache it would look something like the following: 

    RewriteEngine On
    RewriteRule "(^|/)\." - [F]
    RewriteCond %{DOCUMENT_ROOT}%{REQUEST_FILENAME} !-f
    RewriteCond %{DOCUMENT_ROOT}%{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} !=/favicon.ico
    RewriteRule ^ /your_www_root/api.php [NC,NS,L]

Please note that this configuration is for a httpd.conf, if you are putting it into an .htaccess file, you may want to remove 
the leading %{DOCUMENT_ROOT} in the corresponding RewriteConds.

The very first RewriteRule is a security-hardening feature, ensuring that system files (the ones typically starting with dot) 
do not accidentally get exposed.

For Nginx, you need to make sure that Nginx is properly configured with PHP-FPM as CGI and the actual configuration in the 
virtualhost may look something like:

    location / {
      # the main router script
      if (!-e $request_filename) {
        rewrite ^/(.*)$ /api.php?q=$1 last;
      }
    }

That's it, for now.