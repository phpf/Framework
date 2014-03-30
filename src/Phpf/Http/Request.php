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
	 * Content type requested.
	 * @var string
	 */
	public $content_type;
	
	/**
	 * Whether to allow method override via Header or param
	 * @var boolean
	 */
	protected $allow_method_override = true;
	
	/**
	 * Indexed array of content types that will be respected.
	 * If an invalid or no type is requested, the default is used.
	 * @see Response
	 * @var array
	 */
	protected $allow_content_types = array('html', 'json', 'jsonp', 'xml');
	
	/**
	 * Build request from $server array
	 */
	public function __construct(array $server = null, array $query = null){
		
		if (empty($server))	{
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
			parse_str($this->query, $this->query_params);
		}
		
		if (isset($server['REQUEST_METHOD'])) {
			$method = $server['REQUEST_METHOD'];
		}
		
		// @TODO reconsider just using php://input on body for all methods
		if (isset($method) && Http::METHOD_POST === $method) {
			$this->body_params = $_POST;
		} else {
			parse_str($this->clean(file_get_contents('php://input')), $this->body_params);
		}
		
		// Override request method if permitted
		if ($this->allow_method_override) {
			if (isset($this->headers['x-http-method-override']))
				$method = $this->headers['x-http-method-override'];
			if (isset($this->query_params['_method']))
				$method = $this->query_params['_method'];
		}
		
		$this->method = strtoupper(trim($method));
		
		// Is this an XHR request?
		if (isset($this->headers['x-requested-with'])) {
			$this->xhr = ('XMLHttpRequest' === $this->headers['x-requested-with']);
		}
		
		$this->params = array_merge($this->query_params, $this->body_params);
		
		// switch to keys for isset() 
		$this->allow_content_types = array_fill_keys($this->allow_content_types, true);
	}
	
	/**
	 * Magic __get() gets parameters.
	 */
	public function __get($var) {
		return isset($this->params[$var]) ? $this->params[$var] : null;
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
	 * Boolean method/xhr checker.
	 */
	public function is($thing) {
		switch(strtoupper($thing)) {
			case Http::METHOD_GET :
				return Http::METHOD_GET === $this->method;
			case Http::METHOD_POST :
				return Http::METHOD_POST === $this->method;
			case Http::METHOD_PUT :
				return Http::METHOD_PUT === $this->method;
			case Http::METHOD_HEAD :
				return Http::METHOD_HEAD === $this->method;
			case Http::METHOD_DELETE :
				return Http::METHOD_DELETE === $this->method;
			case Http::METHOD_OPTIONS :
				return Http::METHOD_OPTIONS === $this->method;
			case Http::METHOD_PATCH :
				return Http::METHOD_PATCH === $this->method;
			case 'XHR' :
			case 'AJAX' :
				return $this->isXhr();
			default :
				return null;
		}
	}
	
	/** Am I a GET request? */
	public function isGet(){
		return $this->is(Http::METHOD_GET);
	}
	
	/** Am I a POST request? */
	public function isPost(){
		return $this->is(Http::METHOD_POST);
	}
	
	/**  Am I a PUT request? */
	public function isPut(){
		return $this->is(Http::METHOD_PUT);
	}
	
	/** Am I a HEAD request? */
	public function isHead(){
		return $this->is(Http::METHOD_HEAD);
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
	 * Returns true if given response content-type is allowed.
	 */
	public function isContentTypeAllowed( $type ) {
		return isset($this->allow_content_types[$type]);
	}
	
	/**
	 * Sets the requested content type if valid, for later reference.
	 */
	public function setContentType( $type ) {
		
		if ($this->isContentTypeAllowed($type)) {
			$this->content_type = $type;
			return true;
		}
		
		return false;
	}

	/**
	 * Strips naughty text and slashes from uri components
	 * @uses filter_var() with FILTER_SANITIZE_STRING
	 */
	protected function clean( $str ){
		return trim(filter_var($str, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH), '/');	
	}
	
}