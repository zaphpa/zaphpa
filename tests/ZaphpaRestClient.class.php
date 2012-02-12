<?php

/**
*
* Simple HTTP REST Client. 
* For full disclosure this is a shameless rip-off from: https://github.com/inadarei/settee/blob/master/src/classes/ZaphpaRestClient.class.php 
* That is: if you can call "stealing" from one's self "shameless" :)
*
*/
class ZaphpaRestClient {
  
  /**
  * HTTP Timeout in Milliseconds
  */
  const HTTP_TIMEOUT = 2000;
  
  private $base_url;
  private $curl;
  
  private static $curl_workers = array();

  /**
  * Singleton factory method   
  */
  static function get_instance($base_url) {

    if (empty(self::$curl_workers[$base_url])) {
      self::$curl_workers[$base_url] = new ZaphpaRestClient($base_url);
    }
    
    return self::$curl_workers[$base_url];
  }
  
  /**
  * Class constructor
  */
  private function __construct($base_url) {
    $this->base_url = $base_url;

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_USERAGENT, "Zaphpa Automated Tests Client/1.0");
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($curl, CURLOPT_TIMEOUT_MS, self::HTTP_TIMEOUT);
    curl_setopt($curl, CURLOPT_FORBID_REUSE, false); // Connection-pool for CURL

    $this->curl = $curl;    
  }

 /**
  * Class destructor cleans up any resources
  */
  function __destruct() {
     curl_close($this->curl);
  }

 /**
  * HTTP HEAD
  *
  * @return
  *     Raw HTTP Headers of the response.
  *
  * @see: http://www.php.net/manual/en/context.params.php
  * 
  */
  function http_head($uri) {
    curl_setopt($this->curl, CURLOPT_HEADER, 1);

    $full_url = $this->get_full_url($uri);
    curl_setopt($this->curl, CURLOPT_URL, $full_url);
    curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'HEAD');
    curl_setopt($this->curl, CURLOPT_NOBODY, true);


    $response = curl_exec($this->curl);
    // Restore default values
    curl_setopt($this->curl, CURLOPT_NOBODY, false);
    curl_setopt($this->curl, CURLOPT_HEADER, false);
    
    $resp_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    if ($resp_code == 404 ) {
      throw new ZaphpaRestClientException("Couch document not found at: '$full_url'");
    }

    if (function_exists('http_parse_headers')) {
      $headers = http_parse_headers($response);
    }
    else {
      $headers = $this->_http_parse_headers($response);
    }
    
    return $headers;
  }

  /**
   * Backup PHP impl. for when PECL http_parse_headers() function is not available
   *
   * @param  $header
   * @return array
   * @source http://www.php.net/manual/en/function.http-parse-headers.php#77241
   */
  private function _http_parse_headers( $header ) {
    $retVal = array();
    $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
    foreach( $fields as $field ) {
        if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
            $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
            if( isset($retVal[$match[1]]) ) {
                $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
            } else {
                $retVal[$match[1]] = trim($match[2]);
            }
        }
    }
    return $retVal;
  }

  /**
  * HTTP GET
  */
  function http_get($uri, $data = array()) {
    $data = (is_array($data)) ? http_build_query($data) : $data;
    if (!empty($data)) {
      $uri .= "?$data";
    }
    return $this->http_request('GET', $uri);
  }
  
  /**
  * HTTP PUT
  */
  function http_put($uri, $data = array()) {
    return $this->http_request('PUT', $uri, $data);
  }

  /**
  * HTTP DELETE
  */
  function http_delete($uri, $data = array()) {
    return $this->http_request('DELETE', $uri, $data);
  }

  /**
   * Generic implementation of a HTTP Request.
   *
   * @param $http_method
   * @param  $uri
   * @param array $data
   * @return
   *  an array containing json and decoded versions of the response.
   */
  private function http_request($http_method, $uri, $data = array()) {
    $data = (is_array($data)) ? http_build_query($data) : $data;

    if (!empty($data)) {
      curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-Length: ' . strlen($data)));
      curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
    }

		$full_url = $this->get_full_url($uri);
    curl_setopt($this->curl, CURLOPT_URL, $full_url);
    curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, $http_method);

    $response = curl_exec($this->curl);
    $response_decoded = $this->decode_response($response);
    $response = array('json' => $response, 'decoded'=>$response_decoded);

    $this->check_status($response, $full_url);

    return (object)$response;
  }
  
  /**
   * Check http status for safe return codes
   *
   * @throws ZaphpaRestClientException
   */
  private function check_status($response, $full_url) {
    $resp_code = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

    if ($resp_code < 199 || $resp_code > 399 || !empty($response['decoded']->error)) {
      $msg = "Server returned: \"HTTP 1.1. $resp_code\" \nURL: $full_url \nERROR: " . $response['json'];
      throw new ZaphpaRestClientException($msg);
    }
  }

  /**
   * @param  $path
   *    Full path to a file (e.g. as returned by PHP's realpath function).
   * @return void
   */
  public function file_mime_type ($path)  {
    $ftype = 'application/octet-stream';
    
    if (function_exists("finfo_file")) {
      $finfo = new finfo(FILEINFO_MIME_TYPE | FILEINFO_SYMLINK);
      $fres = $finfo->file($path);
      if (is_string($fres) && !empty($fres)) {
         $ftype = $fres;
      }
    }

    return $ftype;
  }

  /**
   * @param  $content
   *    content of a file in a string buffer format.
   * @return void
   */
  public function content_mime_type ($content)  {
    $ftype = 'application/octet-stream';

    if (function_exists("finfo_file")) {
      $finfo = new finfo(FILEINFO_MIME_TYPE | FILEINFO_SYMLINK);
      $fres = $finfo->buffer($content);
      if (is_string($fres) && !empty($fres)) {
         $ftype = $fres;
      }
    }

    return $ftype;
  }
  

  /**
   *
   * @param $json
   *    json-encoded response
   * 
   * @return
   *    decoded PHP object
   */
  private function decode_response($json) {
    return json_decode($json);
  }

  /**
  * Get full URL from a partial one
  */
  private function get_full_url($uri) {
    // We do not want "/", "?", "&" and "=" separators to be encoded!!!
    $uri = str_replace(array('%2F', '%3F', '%3D', '%26'), array('/', '?', '=', '&'), urlencode($uri));
    return $this->base_url . '/' . $uri;
  }
}

class ZaphpaRestClientException extends Exception {}
