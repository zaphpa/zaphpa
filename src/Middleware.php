<?php

namespace Zaphpa;

abstract class Middleware {

    const ALL_METHODS = '*';

    public $scope = array();
    public static $context = array();
    public static $routes = array();

    /**
     *  Restrict a Middleware hook to certain paths and HTTP methods.
     *
     *  No actual restriction takes place in this method.
     *  We simply place the $methods array into $this->scope, keyed by its $hook.
     *
     *  @param string $hook
     *    A Middleware hook, expecting either 'preroute' or 'prerender'.
     *  @param array $rules
     *    An associative array of paths and their allowed methods:
     *    - path: A URL route string, the same as are used in $router->addRoute().
     *      - methods: An array of HTTP methods that are allowed, or an '*' to match all methods.
     *
     *  @return Middlware
     *    The current Middleware object, to allow for chaining a la jQuery.
     */
    public function restrict($hook, $methods, $route) {
        $this->scope[$hook][$route] = $methods;
        return $this;
    }

    /**
     *  Determine whether the current route has any route restrictions for this Middleware.
     *
     *  If the Middleware has restrictions for a given $hook, we check for the current route.
     *  If the current route is in the list of allowed paths, we check that the
     *  request method is also allowed. Otherwise, the current route needn't run the $hook.
     *
     *  @param string $hook
     *    A Middleware hook, expecting either 'preroute' or 'prerender'.
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

} // end Middleware