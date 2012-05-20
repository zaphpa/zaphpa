---
layout: index
title: Zaphpa PHP micro-router.
---

<header class="masthead">
  <h1 id="headline">Zaphpa = PHP + REST</h1>
  <div id="subhead">Intuitive, flexible and powerful HTTP router.</div>  
  <div class="project-links">
<a href="doc.html" class="btn btn-primary btn-large">Read Docs</a>
<a href="https://github.com/zaphpa/zaphpa/" class="btn btn-large">View on GitHub</a>
<a href="https://github.com/zaphpa/zaphpa/zipball/master" class="btn btn-large">Download Snapshot</a>
  </div>
</header>

<hr class="soften">

    require_once(__DIR__ . '/vendor/zaphpa/zaphpa.lib.php');
    $router = new Zaphpa_Router();
    
    $router->addRoute(array(
    	'path'  => '/users/{id}',
    	'get'   => array('UserController', 'getUser'),
    	'post'   => array('UserController', 'updateUser'),
    ));
    
    $router->route();

<div class="marketing">
  <h2>Created to make REST in PHP easy.</h2>

   <p>Zaphpa, pronounced as the last name of Frank Zappa, is a routing microkernel inspired by 
   <a href="http://www.sinatrarb.com/">Sinatra.rb</a>, and implemented in PHP (hence the extra geeky "h" 
   in the name creating the sub-pattern of: "php").</p>
   
	 <p>Zaphpa is a lightweight library that excels at single purpose: free API developers from 
	 the boilerplate of handling HTTP requests and implementing REST.</p> 
	 
	 <p>You can think of it as an equivalent of Sinatra (in Ruby) or Express.js (in Javascript) 
	 frameworks for PHP, if you will.</p>    
</div><!-- /.marketing -->

<hr class="soften">

<footer class="footer">
  <p>Code licensed under the <a href="https://github.com/zaphpa/zaphpa/#license" target="_blank">MIT License</a>. Documentation licensed under <a href="http://creativecommons.org/licenses/by-sa/3.0/">CC BY-SA 3.0</a>.</p>
</footer>

