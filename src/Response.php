<?php

namespace Zaphpa;

/**
 * Response class
 */
class Response {

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
        return $this;
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
     *  @param integer $code
     *      HTTP response Code. Defaults to 200
     *  @param string $format
     *      Output mime type. Defaults to request format
     *
     *  @return Response current respons object, so you can chain method calls on a response object.
     */
    public function flush($code = null, $format = null) {
        $this->verifyResponseCode($code);

        // If no format was set explicitly, use the request format for response.
        if (!empty($format)) {
            if (headers_sent()) {
                throw new Exceptions\InvalidResponseStateException("Response format already sent: {$this->format}");
            }
            $this->setFormat($format);
        }

        // Set default values (200 and request format) if nothing was set explicitely
        if (empty($this->format)) { $this->format = $this->req->format; }
        if (empty($this->code)) { $this->code = 200; }

        $this->sendHeaders();

        /* Call preprocessors on each BaseMiddleware impl */
        foreach (Router::$middleware as $m) {
            if ($m->shouldRun()) {
                $m->prerender($this->chunks);
            }
        }

        $out = implode('', $this->chunks);
        $this->chunks = array(); // reset
        echo ($out);
        return $this;
    }

    protected function verifyResponseCode($code) {
        if (!empty($code)) {
            if (headers_sent()) {
                throw new Exceptions\InvalidResponseStateException("Response code already sent: {$this->code}");
            }

            $codes = $this->codes();
            if (array_key_exists($code, $codes)) {
                //$protocol = $this->req->protocol;
                $this->code = $code;
            } else {
                throw new Exceptions\InvalidResponseCodeException("Invalid Response Code: $code");
            }
        }
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
        return $this;
    }

    public function getFormat() {
        return $this->format;
    }

    /**
    * Add an HTTP header key/value pair
    *
    * $key string
    * $val string
    *
    */
    public function addHeader($key, $val) {
        if (is_array($val)) {
            $val = implode(", ", $val);
        }
        $this->headers[] = "{$key}: $val";
        return $this;
    }

    /**
     * Send headers to instruct browser not to cache this content
     * See http://stackoverflow.com/a/2068407
     */
    public function disableBrowserCache() {
        $this->headers[] = 'Cache-Control: no-cache, no-store, must-revalidate'; // HTTP 1.1.
        $this->headers[] = 'Pragma: no-cache'; // HTTP 1.0.
        $this->headers[] = 'Expires: Thu, 26 Feb 1970 20:00:00 GMT'; // Proxies.
        return $this;
    }

    /**
     *  Send entire collection of headers if they haven't already been sent
     */
    public function sendHeaders($noContentType = false) {
        if (!headers_sent()) {
            foreach ($this->headers as $header) {
                header($header);
            }
            if ($noContentType == false) {
                header("Content-Type: $this->format;", true, $this->code);
            }
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
            '429' => 'Too Many Requests',
            '500' => 'Internal Server Error',
            '501' => 'Not Implemented',
            '502' => 'Bad Gateway',
            '503' => 'Service Unavailable',
            '504' => 'Gateway Timeout',
            '505' => 'HTTP Version Not Supported',
        );
    }

} // end Request