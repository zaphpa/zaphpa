---
layout: default
title: Zaphpa RESTful Microframework
---

# What is Zaphpa?

Zaphpa, pronounced as the lastname of Frank Zappa, is a routing microkernel inspired by [Sinatra.rb](http://www.sinatrarb.com/), and implemented in PHP (hence the extra geeky "h" in the name creating the sub-pattern of "php.).

Zaphpa is a swiss-army tool for developing RESTful HTTP APIs in PHP. It's a lightweight library that has a single purpose: free API developers from the boilerplate of handling HTTP requests and implementing REST. 

You can think of it as an equivalent of Sinatra (Ruby) or Express.js (Javascript) frameworks for PHP, if you will.

# Quick Introduction

To start serving RESTful HTTP requests, you need to go through three simple steps:

1. Setup Zaphpa Library
1. Instantiate and configure a router object
1. Write callbacks/controllers.

## Setting Up Zaphpa Library

You need to register a PHP script to handle all HTTP requests. For Apache it would look something like the following: 

<pre>
RewriteEngine On
RewriteRule "(^|/)\." - [F]
RewriteCond %{DOCUMENT_ROOT}%{REQUEST_FILENAME} !-f
RewriteCond %{DOCUMENT_ROOT}%{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !=/favicon.ico
RewriteRule ^ /your_www_root/api.php [NC,NS,L]
</pre>

Please note that this configuration is for a httpd.conf, if you are putting it into an .htaccess file, you may want to remove the leading %{DOCUMENT_ROOT} in the corresponding RewriteConds.

The very first RewriteRule is a security-hardening feature, ensuring that system files (the ones typically starting with dot) do not accidentally get exposed.

For Nginx, you need to make sure that Nginx is properly configured with PHP-FPM as CGI and the actual configuration in the virtualhost may look something like:
<pre>
location / {
  # the main router script
  if (!-e $request_filename) {
    rewrite ^/(.*)$ /api.php?q=$1 last;
  }
}
</pre>

## Instantiating And Configuring A Router

For a very simple case of getting specific user object, the code of api.php would look something like:

<pre>
require_once(dirname(__FILE__) . '/zaphpa/zaphpa.lib.php');

$router = new Zaphpa_Router();

$router->addRoute(array(
	  'path'     => '/users/{id}',
	  'handlers' => array(
	    'id'         => Zaphpa_Constants::PATTERN_DIGIT, //enforced to be numeric
	  ),
	  'get'      => array('MyController', 'getPage'),
	)
);

$router->route();
</pre>

In this example, {id} is a URI parameter of the type "digit", so `MyController->getPage()` function will get control to serve URLs like:

* http://example.com/users/32424
* http://example.com/users/23

However, we asked the library to ascertain that the {id} parameter is a number by attaching a validating handler: "Zaphpa_Constants::PATTERN_DIGIT" to it. As such, following URL will not be handed over to the `MyController->getPage()` callback:

* http://example.com/users/ertla
* http://example.com/users/asda32424
* http://example.com/users/32424sfsd
* http://example.com/users/324sdf24

# Pre-defined Validator Types

Zaphpa allows indicating completely custom function callbacks as validating handlers, but for convenience it also provides number of pre-defined, common validators:
<pre>
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
</pre>

You may be able to guess the functionality from the regexp patterns associated with each pre-defined validator, but let's go through the expected behavior of each one of them:

* PATTERN_NUM - ensures a path element to be numeric
* PATTERN\_DIGIT - alias to PATTERN\_NUM 
* PATTERN\_MD5 - ensures a path element to be valid MD5 hash
* PATTERN\_ALPHA - ensures a path element to be valid alpha-numeric string (i.e. latin characters and numbers, as defined by \w pattern of regular expressions).
* PATTERN\_ARGS - is a more sophisticated case that takes some explanation. It tries to match multiple path elements and could be useful in URLs like: <pre>/news/212424/**us/politics/elections**/some-title-goes-here/2012</pre> 
where "us/politics/elections" is a part with variable number of "categories". To parse such URL you could define a validator like: <script src="https://gist.github.com/1900311.js?file=gistfile1.txt"></script> and you would get the function arguments in the callback as: <script src="https://gist.github.com/1900324.js?file=gistfile1.txt"></script>
* PATTERN\_ARGS\_ALPHA - acts the exact same way as PATTERN\_ARGS but limits character set to alpha-numeric ones.
* PATTERN\_ANY (default) - matches any one argument
* PATTERN\_WILD\_CARD - "greedy" version of PATTERN\_ANY that can match multiple arguments
* PATTERN\_YEAR - matches a 4-digit representation of a year.
* PATTERN\_MONTH - matches 1 or 2 digit representation of a month
* PATTERN\_DAY - matches 1 or 2 digit representation of a numeric day.

For more custom cases, you can use a custom regex:
<script src="https://gist.github.com/1900357.js?file=gistfile1.txt"></script>

or attach a validator/parser callback function where you can do whatever you need: 
<script src="https://gist.github.com/1900339.js?file=gistfile1.txt"></script>

The output of a custom parser callback should match that of a regex call i.e.: should return a parsed array of matches or null.


# Example Controllers/Callbacks

<pre>
class MyController {

	public function getPage($req, $res) {
		$res->setFormat("json");
		$res->add(json_encode($req->params));
       $res->add(json_encode($req->data));
       $res->send(301);    
	}

	public function postPage($req, $res) {
		$res->setFormat("json");
		$res->add(json_encode($req->params));
       $res->add(json_encode($req->data));
       $res->send(301);    
	}

}	
</pre>

When invoked callbacks get two arguments:

1. $req (request) object contains data parsed from the request, and can include properties like:
    1. $params - which contains all the placeholders matched in the URL (e.g. the value of the "id" argument)
    1. $data  - an array that contains HTTP data. In case of HTTP GET it is: parsed request parameters, for HTTP POST, PUT and DELETE requests: data variables contained in the HTTP Body of the request.
    1. $version - version of the API if one is versioned (not yet implemented)
    1. $format - data format that was requested (e.g. XML, JSON etc.)
		Following is an example request object:
		<script src="https://gist.github.com/1353603.js?file=HTTPOutput.php"></script>
2. $res (response) object is used to incrementally create content. You can add chunks of text to the output buffer by calling: $res->add (String) and once you are done you can send entire buffer to the HTTP client by issuing: $res->send(<HTTP_RESPONSE_CODE>). HTTP_RESPONSE_CODE is an optional parameter which defaults to (you guessed it:) 200.

# Convenience Functions

1. $req->get_var('varname') - since $req object populates $data object, you can access request variables (request parameters or HTTP Body data, depending on the type of request) through the array directly. However due to malformed clients or some other application logic, variable may not be set, causing PHP to throw a warning. Instead of having you check each call to $req->data['varname'] on being empty Zaphpa provides a convenience method: $req->get_var('varanme').

# A More Advanced Router Example

<pre>
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
</pre>

# Routing to Entities

So far we have discussed routing individual URI patterns. However, when building a RESTful API, you often need to create full Resources or Endpoints - API lingo for objects that can be managed in a full: Create, Read, Update, Delete (CRUD) lifecycle.

One way you can do this is to fully declare all four routes. But that would mean a lot of duplicated configuration. And we hate code duplication, so here's a nifty shortcut you can use:

<pre>
$router->addRoute(array(
      'path'     => '/books/{id}',
      'handlers' => array(
        'id'         => Zaphpa_Constants::PATTERN_DIGIT, 
      ),
      'get'      => array('MyController', 'getBook'),
      'post'     => array('MyController', 'createBook'),
      'put'      => array('MyController', 'updateBook'),
      'delete'     => array('MyController', 'deleteBook'),
      'file'     => 'controllers/bookcontroller.php'
    )
);
</pre>
