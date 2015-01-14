---
layout: index
title: Zaphpa - Intuitive API Microframework for PHP
---

<header class="masthead">
  <h1 id="headline">Zaphpa = APIs for PHP</h1>
  <div id="subhead">Intuitive, flexible & powerful API micro-router.</div>  
  <div class="project-links">
<a href="doc.html" class="btn btn-primary btn-large">Read Docs</a>
<a href="https://github.com/zaphpa/zaphpa/" class="btn btn-large">GitHub</a>
<a href="https://packagist.org/packages/zaphpa/zaphpa" class="btn btn-large">Packagist</a>
<a href="https://github.com/zaphpa/zaphpa/releases" class="btn btn-large">Legacy Versions</a>
  </div>
</header>

```php
require_once('./vendor/autoload.php');
$router = new \Zaphpa\Router();

$router->attach('\Zaphpa\Middleware\ZaphpaAutoDocumentator', '/apidocs'); //auto-docs middleware
$router->attach('\Zaphpa\Middleware\MethodOverride');

$router->addRoute(array(
  'path'  => '/users/{id}',
  'get'   => array('\MyAwesomeApp\UserController', 'getUser'),
  'post'   => array('\MyAwesomeApp\UserController', 'updateUser'),
));    
$router->route();
```

<div class="intro">
<h2>Created to make REST in PHP easy.</h2>

<p>Zaphpa, pronounced as the last name of Frank Zappa, is a routing microkernel inspired by 
<a href="http://www.sinatrarb.com/">Sinatra.rb</a>, and implemented in PHP (hence the extra geeky "h" 
in the name creating the sub-pattern of: "php").</p>

<p>Zaphpa is a lightweight library that excels at single mission: free PHP API developers from 
the boilerplate of handling HTTP requests and implementing REST.</p> 

<p>You can think of it as an equivalent of Sinatra (in Ruby) or Express.js (in Javascript) 
frameworks for PHP, if you will.</p>   

<h2>Who uses it?</h2>
<p>Zaphpa is actively used by <a href="http://npr.org">NPR</a>'s software teams in the Digital Media and Digital Services
divisions, benefiting from thorough bug-fixing and various contributions by: <a href="http://github.com/inadarei">@inadarei</a>,  
<a href="http://github.com/karoun">@karoun</a>,
<a href="http://github.com/randallsquared">@randallsquared</a>, <a href="http://github.com/johnymonster">@johnymonster</a>, 
<a href="http://github.com/jsgrosman">@jsgrosman</a> and <a href="http://github.com/d1b1">@d1b1</a>.	 
	  
</div><!-- /.intro -->

<hr class="soften">

<footer class="footer">
  <p>Code licensed under the <a href="https://github.com/zaphpa/zaphpa/#license" target="_blank">MIT License</a>. Documentation licensed under <a href="http://creativecommons.org/licenses/by-sa/3.0/">CC BY-SA 3.0</a>.</p>
</footer>

