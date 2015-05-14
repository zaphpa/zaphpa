<?php

namespace Zaphpa;

/**
 * HTTP Request class
 */
class Request {
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

        $this->method = Router::getRequestMethod();
        $this->grabRequestMetadata();

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

    }

    protected function grabRequestMetadata() {
        $this->clientIP = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '';
        $this->clientIP = (empty($this->clientIP) && !empty($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : '';

        $this->userAgent = empty($_SERVER['HTTP_USER_AGENT']) ? '' : $_SERVER['HTTP_USER_AGENT'];
        $this->protocol = !empty($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : null;

        $this->parse_special('encodings', 'HTTP_ACCEPT_ENCODING', array('utf-8'));
        $this->parse_special('charsets', 'HTTP_ACCEPT_CHARSET', array('text/html'));
        $this->parse_special('accepted_formats', 'HTTP_ACCEPT');
        $this->parse_special('languages', 'HTTP_ACCEPT_LANGUAGE', array('en-US'));
    }

    /**
     * Requested output format, if any.
     * Format in the URL request string takes priority over the one in HTTP headers, defaults to HTML.
     */
    protected function contentNego() {
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
     * Parses some packed $_SERVER variables (e.g. 'encoding', 'charsets' etc.) into more useful arrays.
     *
     * @param string $varname - alias under which the variable will be 
     *                          attached to the current Request object
     * @param string $argname - the name of the argument in $_SERVER
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

} // end Request