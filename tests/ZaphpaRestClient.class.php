<?php

/**
 * @file
 * A simple HTTP REST client.
 *
 * @see https://github.com/inadarei/settee/blob/master/src/classes/SetteeRestClient.class.php
 */
class ZaphpaRestClient {
  
  /** HTTP Timeout in Milliseconds */
  const HTTP_TIMEOUT = 2000;
  
  private $base_url;
  private $curl;
  
  private static $curl_workers = array();

  /**
   * Get an instance of the REST client from our singleton factory.
   *  
   * @param string $base_url 
   *   The location of the server to which to send requests.
   */
  static function get_instance($base_url) {
    if (empty(self::$curl_workers[$base_url])) {
      self::$curl_workers[$base_url] = new ZaphpaRestClient($base_url);
    }
    
    return self::$curl_workers[$base_url];
  }
  
  /**
   * Make an instance of the REST client, starting and configuring a cURL connection.
   *  
   * @param string $base_url 
   *   The location of the server to which to send requests.
   */
  private function __construct($base_url) {
    $this->base_url = $base_url;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_USERAGENT, 'Zaphpa Automated Tests Client/1.0');
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HEADER, 1);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT_MS, self::HTTP_TIMEOUT);
    curl_setopt($curl, CURLOPT_FORBID_REUSE, false); // Connection-pool for CURL

    $this->curl = $curl;    
  }

  /** Close the cURL connection being used for this class. */
  public function __destruct() {
    curl_close($this->curl);
  }

  /**
   * Send an HTTP HEAD request.
   * 
   * Zaphpa does not currently provide a correct HEAD response automatically
   * so we circumvent this by sending a GET request and filtering out the headers.
   * 
   * @param string $uri
   *   The URL path to which the request will be sent.
   * @param array|string $data
   *   An array or string of query parameters to be appeneded to the URL.   
   * 
   * @return array
   *   The HTTP headers of the response, keyed by header name.
   */
  public function head($uri, $data = array()) {
    $ret = $this->get($uri, $data);
    return empty($ret->headers) ? array() : $ret->headers;
  }

  /**
   * Send an HTTP GET request.
   * 
   * @param string $uri
   *   The URL path to which the request will be sent.
   * @param array|string $data
   *   An array or string of query parameters to be appeneded to the URL.
   * 
   * @return
   *   The raw HTTP headers of the response.
   */
  public function get($uri, $data = array()) {
    $data = is_array($data) ? http_build_query($data) : $data;
    if (!empty($data)) {
      $uri .= "?$data";
    }
    return $this->http_request('GET', $uri);
  }
  
  /**
   * Send an HTTP PUT request.
   * 
   * @param string $uri
   *   The URL path to which the request will be sent.
   * @param array|string $data
   *   An array or string of parameters to be sent to the server.
   * 
   * @return
   *   The raw HTTP headers of the response.
   */
  public function put($uri, $data = array()) {
    return $this->http_request('PUT', $uri, $data);
  }

  /**
   * Send an HTTP DELETE request.
   * 
   * @param string $uri
   *   The URL path to which the request will be sent.
   * @param array|string $data
   *   An array or string of parameters to be sent to the server.
   * 
   * @return
   *   The raw HTTP headers of the response.
   */
  public function delete($uri, $data = array()) {
    return $this->http_request('DELETE', $uri, $data);
  }

  /**
   * Generic implementation of an HTTP request.
   * 
   * @param string $http_method
   *   The desired HTTP method, one of GET, PUT, POST, DELETE, HEAD, PATCH, OPTIONS.
   * @param string $uri
   *   The requested URI path.
   * @param array|string $data
   *   The serialized data to POST to the server.
   * 
   * @return object
   *   An object containing raw and decoded versions of the response, along with any headers.
   */
  private function http_request($http_method, $uri, $data = array()) {
    $data = is_array($data) ? http_build_query($data) : $data;

    if (!empty($data)) {
      curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($data)));
      curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
    }

		$full_url = $this->get_full_url($uri);
    curl_setopt($this->curl, CURLOPT_URL, $full_url);
    curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $http_method);

    $response = curl_exec($this->curl);

    $header_size = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $header_size);
    $content = substr($response, $header_size);

    if (function_exists('http_parse_headers')) {
      $headers = http_parse_headers($headers);
    } else {
      $headers = $this->_http_parse_headers($headers);
    }

    $response = array(
      'raw_data' => $content,
      'decoded'  => json_decode($content),
      'headers'  => $headers,
    );

    $this->check_status($response, $full_url);

    return (object) $response;
  }
  
  /**
   * Check HTTP status for successful return codes.
   * 
   * @param array $response
   *   An array containing cURL response from $this->http_request().
   * @param string $full_url
   *   A URL string from $this->get_full_url().
   * 
   * @throws ZaphpaRestClientException
   */
  private function check_status($response, $full_url) {
    $resp_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

    if ($resp_code < 199 || $resp_code > 399 || !empty($response['decoded']->error)) {

      $msg = array(
        "Server returned: HTTP/1.1 $resp_code", 
        "URL: $full_url", 
        "ERROR: {$response['raw_data']}",
      );

      throw new ZaphpaRestClientException(implode("\n", $msg), $resp_code);
    }
  }

  /**
   * Get the full URL from a URI path, encoded per RFC 3986.
   * 
   * @see http://www.php.net/manual/en/function.urlencode.php#97969
   * 
   * @param string $uri
   *   A URL fragment containing just the desired path.
   * 
   * @return string
   *   A full URL with proper character encoding and a full domain/host.
   */
  private function get_full_url($uri) {
    $entities = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D');
    $replacements = array('!', '*', "'", '(', ')', ';', ':', '@', '&', '=', '+', '$', ',', '/', '?', '%', '#', '[', ']');
    $uri = str_replace($entities, $replacements, urlencode($uri));
    return "{$this->base_url}/$uri";
  }

  /**
   * Backup implementation of http_parse_headers() when PECL isn't available.
   * 
   * @see http://www.php.net/manual/en/function.http-parse-headers.php#77241
   * 
   * @param string $header
   *   A string containing HTTP response headers from cURL.
   * 
   * @return array
   *   An array containing response info, keyed by header name.   
   */
  private function _http_parse_headers($header) {
    $retVal = array();
    $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
    foreach ($fields as $field) {
      if (preg_match('/([^:]+): (.+)/m', $field, $match)) {
        $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
        if (isset($retVal[$match[1]])) {
          $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
        } else {
          $retVal[$match[1]] = trim($match[2]);
        }
      }
    }
    return $retVal;
  }
}

class ZaphpaRestClientException extends Exception {}
