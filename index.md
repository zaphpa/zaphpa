---
layout: default
title: Zaphpa Library
---

# What is Zaphpa?

Zaphpa is a colleague of <a href="http://www.sinatrarb.com/">Sinatra</a>, wearing a PHPeather hat. 

<img src="http://gravatar.com/avatar/fc564aeb3c58786c628277b58dd9943c?s=85" />

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

## Instantiating And Configuring A Router

For a very simple case of getting specific user object, the code of api.php would look something like:

<pre>
require_once(dirname(__FILE__) . '/zaphpa/zaphpa.lib.php');

$router = new Zaphpa_Router();

$router->addRoute(array(
	  'path'     => '/users/{id}',
	  'handlers' => array(
	    'id'         => Zaphpa_Constants::PATTERN_DIGIT, //regex
	  ),
	  'get'      => array('MyController', 'getPage'),
	)
);

$router->route();
</pre>

In this example, {id} is a URI parameter, so `MyController->getPage()` function will get control to serve URLs like:

* http://example.com/users/32424
* http://example.com/users/23

However, we asked the library to ascertain that the {id} parameter is a number by attaching a validating handler: "Zaphpa_Constants::PATTERN_DIGIT" to it. As such, following URL will not be handed over to the `MyController->getPage()` callback:

* http://example.com/users/ertla
* http://example.com/users/asda32424
* http://example.com/users/32424sfsd
* http://example.com/users/324sdf24

# Example Controllers/Callbacks

<pre>
class MyController {

	public function getPage($req, $res) {
		$res->add(pre($req->params));
	    $res->add(pre($req->data));
	    $res->send(301);    
	}

	public function postPage($req, $res) {
		$res->add(pre($req->params));
	    $res->add(pre($req->data));
	    $res->send(301);    
	}

}	

function pre($o) {
  return strtr('&lt;pre&gt;%s&lt;/pre&gt;', 
               array('%s' => print_r($o, true))
         );
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
