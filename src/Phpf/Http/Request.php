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
	public static $allow_method_override = true;
	
	/**
	 * Built request from $server array
	 */
	public function __construct( array $server = null ){
		
		if ( empty($server) ){
			$server =& $_SERVER;
		}
		
		$uri	= $this->clean(urldecode($server['REQUEST_URI']));
		$query	= $this->clean(urldecode($server['QUERY_STRING']));
		
		// Remove query string from uri and convert to array
		if ( !empty($query) ){
			$uri = str_replace("?$query", '', $uri);
		}
		
		$this->uri		= $uri;
		$this->query	= $query;
		$this->headers	= Http::requestHeaders($server);
		
		$method = $server['REQUEST_METHOD'];
		
		$this->query_params = $_GET;
		
		if ( 'GET' === $method || 'POST' === $method ){
			$this->body_params = $_POST;
		} else {
			parse_str($this->clean(file_get_contents('php://input')), $this->body_params);
		}
		
		// Override method if permitted
		if ( self::$allow_method_override ){
			
			if ( isset($this->headers['x-http-method-override'] ) )
				$method = $this->headers['x-http-method-override'];
			
			if ( isset($this->query_params['_method']) )
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
		$_REQUEST = $this->params;
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
		self::$allow_method_override = false;
		return $this;
	}
	
	/**
	 * Allow HTTP method override via header and query param.
	 */
	public function allowMethodOverride(){
		self::$allow_method_override = true;
		return $this;
	}
	
	/**
	* Strips naughty text and slashes from uri components
	*/
	protected function clean( $str ){
		return trim(htmlentities(strip_tags($str), ENT_COMPAT), '/');	
	}
	
}