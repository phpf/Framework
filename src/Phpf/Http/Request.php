<?php
/**
 * @package Phpf.Http
 */

namespace Phpf\Http;

use Phpf\Util\Str;

class Request {
	
	/**
	 * HTTP request method
	 * @var string
	 */
	public $method;
	
	/**
	 * Request URI
	 * @var string
	 */
	public $uri;
	
	/**
	 * Request query string
	 * @var string
	 */
	public $query;
	
	/**
	 * Request HTTP headers
	 * @var array
	 */
	public $headers = array();
	
	/**
	 * Query parameters.
	 * @var array
	 */
	public $query_params = array();
	
	/**
	 * Request body parameters.
	 * @var array
	 */
	public $body_params = array();
	
	/**
	 * Request path parameters.
	 * @var array
	 */
	public $path_params = array();
	
	/**
	 * All parameters (query, path, and body) combined.
	 * @var array
	 */
	public $params = array();
	
	/**
	 * Whether request is an XML HTTP request
	 * @var boolean
	 */
	public $xhr = false;
	
	/**
	 * Whether to allow method override via Header or param
	 * @var boolean
	 */
	public $allow_method_override = true;
	
	/**
	 * Build request from $server array
	 */
	public function __construct( array $server = null, array $query_params = null ){
		
		if ( empty($server) ){
			$server =& $_SERVER;
		}
		
		$this->headers = Http::requestHeaders($server);
		
		$this->query = $this->clean(urldecode($server['QUERY_STRING']));
		
		// Set request path
		if (isset($server['PATH_INFO'])) {
			$uri = urldecode($server['PATH_INFO']);
		} else {
			$uri = urldecode($server['REQUEST_URI']);
			// Remove query string from path
			$uri = str_replace("?$this->query", '', $uri);
		}
		
		$this->uri = $this->clean($uri);
		
		if (isset($query_params)) {
			$this->query_params = $query_params;
		} else {
			$this->query_params = $_GET;
		}
		
		// Use real request method to determine body params
		if (isset($server['REQUEST_METHOD'])) {
			$method = $server['REQUEST_METHOD'];
		}
		
		// Set body params to $_POST if POST, otherwise use php://input
		if (isset($method) && 'POST' === $method ){
			$this->body_params = $_POST;
		} else {
			parse_str($this->clean(file_get_contents('php://input')), $this->body_params);
		}
		
		// Override request method if permitted
		if ($this->allow_method_override) {
				
			// X-HTTP-METHOD-OVERRIDE header
			if (isset($this->headers['x-http-method-override']))
				$method = $this->headers['x-http-method-override'];
			
			// _method query parameter
			if (isset($this->query_params['_method']))
				$method = $this->query_params['_method'];
		}
		
		$this->method = strtoupper(trim($method));
		
		// Is this an XHR request?
		if ( isset($this->headers['x-requested-with']) ){
			$this->xhr = (bool) 'XMLHttpRequest' === $this->headers['x-requested-with'];
		}
		
		$this->params = array_merge($this->query_params, $this->body_params);
	}
	
	/**
	 * Magic __get() gets parameters.
	 */
	public function __get( $var ){
		
		if ( isset($this->params[$var]) )
			return $this->params[$var];
		
		return null;
	}
	
	/**
	 * Sets matched route path parameters.
	 */
	public function setPathParams( array $params ){
		$this->path_params = $params;
		$this->params = array_merge($this->params, $this->path_params);
		return $this;
	}
	
	/**
	* Returns the request HTTP method.
	*/
	public function getMethod(){
		return $this->method;
	}
	
	/**
	* Returns the request URI.
	*/
	public function getUri(){
		return $this->uri;	
	}
	
	/**
	* Returns the request query string if set.
	*/
	public function getQuery(){
		return $this->query;	
	}
	
	/**
	* Returns all parameter values
	*/
	public function getParams(){
		return $this->params;
	}
	
	/**
	 * Returns true if a parameter is set.
	 */
	public function paramExists($name) {
		return isset($this->params[$name]);
	}
	
	/**
	* Returns a parameter value
	*/
	public function getParam( $name ){
		return isset($this->params[$name]) ? $this->params[$name] : null;
	}
	
	/**
	 * Alias for getParam()
	 * @see getParam()
	 */
	public function param( $name ){
		return $this->getParam($name);	
	}
	
	/**
	* Returns array of parsed headers
	*/
	public function getHeaders(){
		return $this->headers;	
	}
	
	/**
	* Returns a single HTTP header if set.
	*/
	public function getHeader( $name ){
		return isset($this->headers[$name]) ? $this->headers[$name] : null;	
	}
	
	/**
	* Returns true if is a XML HTTP request
	*/
	public function isXhr(){
		return (bool)$this->xhr;	
	}
	
	/**
	 * Am I a GET request?
	 */
	public function isGet(){
		return Http::METHOD_GET === $this->method;
	}
	
	/**
	 * Am I a POST request?
	 */
	public function isPost(){
		return Http::METHOD_POST === $this->method;
	}
	
	/**
	 * Am I a HEAD request?
	 */
	public function isHead(){
		return Http::METHOD_HEAD === $this->method;
	}
	
	/**
	 * Disallow HTTP method override via header and query param.
	 */
	public function disallowMethodOverride(){
		$this->allow_method_override = false;
		return $this;
	}
	
	/**
	 * Allow HTTP method override via header and query param.
	 */
	public function allowMethodOverride(){
		$this->allow_method_override = true;
		return $this;
	}
	
	/**
	* Strips naughty text and slashes from uri components
	*/
	protected function clean( $str ){
		return trim(filter_var($str, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH), '/');	
	}
	
}