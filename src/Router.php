<?php

namespace Zaphpa;


class Router {

    protected $routes  = array();
    public static $middleware = array();

    /** Allowed HTTP Methods. Restricted to only common ones, for security reasons. **/
    protected static $methods = array('get', 'post', 'put', 'patch', 'delete', 'head', 'options');

    /**
     * Add a new route to the configured list of routes
     */
    public function addRoute($params) {

        if (!empty($params['path'])) {

            $template = new Template($params['path']);

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

                Middleware::$routes[$method][$params['path']] = $this->routes[$method][$params['path']];
            }

        }

    }

    /**
     *  Add a new Middleware to the list of middlewares
     */
    public function attach() {

        $args = func_get_args();
        $className = array_shift($args);

        if (!is_subclass_of($className, 'Middleware')) {
            throw new InvalidMiddlewareClass("Middleware class: '$className' does not exist or is not a sub-class of Middleware" );
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

        /* Call preprocessors on each Middleware impl */
        foreach (self::$middleware as $m) {
            $m->preprocess($this);
        }

        $routes = $this->getRoutes();

        foreach ($routes as $route) {
            $params = $route['template']->match($uri);

            if (!is_null($params)) {
                Middleware::$context['pattern'] = $route['template']->getTemplate();
                Middleware::$context['http_method'] = self::getRequestMethod();
                Middleware::$context['callback'] = $route['callback'];

                $callback = Callback_Util::getCallback($route['callback'], $route['file']);
                return $this->invoke_callback($callback, $params);
            }
        }

        if (strcasecmp(Router::getRequestMethod(), "options") == 0)
        {
            return $this->invoke_options();
        }

        throw new Exceptions\InvalidPathException('Invalid path');
    }

    /**
     * Main reason this is a separate function is: in case library users want to change
     * invokation logic, without having to copy/paste rest of the logic in the route() function.
     */
    protected function invoke_callback($callback, $params) {

        $req = new Request();
        $req->params = $params;
        $res = new Response($req);

        /* Call preprocessors on each Middleware impl */
        foreach (self::$middleware as $m) {
            if ($m->shouldRun('preroute')) {
                /* the preroute handled the request and doesn't want the main
                 * code to run.. e.g. if the preroute decided the session wasn't
                 * set and wants to issue a 401, or forward using a 302.
                 */
                if( $m->preroute($req,$res) === FALSE) {
                    return; // nope! don't do anything else.
                }
                // continue as usual.
            }
        }

        return call_user_func($callback, $req, $res);

    }

    protected function invoke_options() {
        $req = new Request();
        $res = new Response($req);

        /* Call preprocessors on each Middleware impl */
        foreach (self::$middleware as $m) {
            if ($m->shouldRun('preroute')) {
                $m->preroute($req,$res);
            }
        }

        $res->setFormat("httpd/unix-directory");
        header("Allow: " . implode(",", array_map('strtoupper',Router::$methods)));
        $res->send(200);

        return true;

    }
}