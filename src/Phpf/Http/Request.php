<?php
/**
 * @package Phpf.Http
 */

namespace Phpf\Http;

class Request {
	
	/**
	 * HTTP request method
	 * @var string
	 */
	protected $method;
	
	/**
	 * Request URI
	 * @var string
	 */
	protected $uri;
	
	/**
	 * Request query string
	 * @var string
	 */
	protected $query;
	
	/**
	 * Request HTTP headers
	 * @var array
	 */
	protected $headers;
	
	/**
	 * Request Cookies
	 * @var array
	 */
	protected $cookies;
	
	/**
	 * Request files
	 * @var array
	 */
	protected $files;
	
	/**
	 * Session
	 * @var SessionInterface
	 */
	protected $session;
	
	/**
	 * Query parameters.
	 * @var array
	 */
	protected $query_params;
	
	/**
	 * Request body parameters.
	 * @var array
	 */
	protected $body_params;
	
	/**
	 * Request path parameters.
	 * @var array
	 */
	protected $path_params;
	
	/**
	 * All parameters (query, path, and body) combined.
	 * @var array
	 */
	protected $params;
	
	/**
	 * Content type requested.
	 * @var string
	 */
	protected $content_type;
	
	/**
	 * Whether to allow method override via "X-Http-Method-Override" header.
	 * Default true.
	 * @var boolean
	 */
	protected $allow_method_override_header = true;
	
	/**
	 * Whether to allow method override via "_method" query parameter.
	 * Default false.
	 * @var boolean
	 */
	protected $allow_method_override_parameter = false;
	
	/**
	 * Whether to globalize the request parameters to $_REQUEST.
	 * @var boolean
	 */
	protected $globalize = true;
	
	/**
	 * Indexed array of content types that will be respected.
	 * If an invalid or no type is requested, the default is used.
	 * @see Response
	 * @var array
	 */
	protected $allow_content_types = array(
		'html' => true, 
		'json' => true, 
		'jsonp' => true, 
		'xml' => true,
	);
	
	/**
	 * Build the request using global variables.
	 */
	public static function createFromGlobals() {
		
		$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] :'GET';
		$query = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
		
		// Set request path
		if (isset($_SERVER['PATH_INFO'])) {
			$uri = urldecode($_SERVER['PATH_INFO']);
		} else {
			$uri = urldecode($_SERVER['REQUEST_URI']);
			// Remove query string from path
			if (false !== ($qpos = strpos($uri, '?'))) {
				$uri = substr($uri, 0, $qpos);	
			}
		}
		
		$headers = http_request_headers();
		
		// Use php://input except when POST w/ enctype="multipart/form-data"
		// @see {@link http://us3.php.net/manual/en/wrappers.php.php}
		if ('POST' === $method && isset($headers['content-type']) 
			&& 'multipart/form-data' === $headers['content-type'])
		{
			$postdata = $_POST;
		} else {
			parse_str(http_request_body(), $postdata);
		}
		
		$request = new static();
		
		return $request->create($method, $uri, $query, $headers, $postdata, $_COOKIE, $_FILES);
	}
	
	/**
	 * Build request from $server array
	 */
	public function create($method, $uri, $query, array $headers, array $postdata = null, array $cookies = null, array $files = null){
		
		// Clean and set request path
		$this->uri = trim(filter_var($uri, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH | FILTER_FLAG_STRIP_BACKTICK), '/');
		
		// set headers, cookies, files
		$this->headers = $headers;
		$this->cookies = $cookies;
		$this->files = $files;
		
		$this->query_params = array();
		
		// Set query string and params
		if (! empty($query)) {
			$this->query = html_entity_decode($query);
			parse_str($this->query, $this->query_params);
		}
		
		// Set post data
		if (isset($postdata)) {
			$this->body_params = $postdata;
			$this->params = array_merge($this->query_params, $this->body_params);
		} else {
			$this->params = $this->query_params;
		}
		
		// Override request method if POST
		if ('POST' === $method) {
			// via header
			if ($this->allow_method_override_header && isset($this->headers['x-http-method-override'])) {
				$method = $this->headers['x-http-method-override']; 
			}
			//via parameter
			if ($this->allow_method_override_parameter && isset($this->query_params['_method'])) {
				$method = $this->query_params['_method'];
			}
		}
		
		$this->method = strtoupper($method);
		
		return $this;
	}
	
	/**
	 * Magic __get()
	 */
	public function __get($var) {
			
		if (isset($this->$var)) {
			return $this->$var;
		}
		
		if (isset($this->params[$var])) {
			return $this->params[$var];
		}
		
		return null;
	}
	
	/**
	 * Sets matched route path parameters.
	 * 
	 * @param array $params Associative array of matched path parameters.
	 * @return $this
	 */
	public function setPathParams(array $params){
		
		$this->path_params = $params;
		$this->params = array_merge($this->params, $this->path_params);
		
		if ($this->globalize) {
			$_REQUEST = $this->params;
		}
		
		return $this;
	}
	
	/**
	 * Sets the session
	 * 
	 * @param SessionInterface $session
	 * @return $this
	 */
	public function setSession(SessionInterface $session) {
		$this->session = $session;
		return $this;
	}
	
	/**
	 * Returns session object
	 * 
	 * @param return SessionInterface
	 */
	public function getSession() {
		return isset($this->session) ? $this->session : null;
	}
	
	/**
	 * Returns true if session is set, otherwise false.
	 * 
	 * @return boolean
	 */
	public function sessionExists() {
		return isset($this->session);
	}
	
	/**
	 * Returns the request HTTP method.
	 * 
	 * @return string HTTP method.
	 */
	public function getMethod() {
		return $this->method;
	}
	
	/**
	 * Returns the request URI.
	 * 
	 * @return string URI
	 */
	public function getUri() {
		return $this->uri;	
	}
	
	/**
	 * Returns the request query string if set.
	 * 
	 * @return string Query
	 */
	public function getQuery() {
		return $this->query;	
	}
	
	/**
	 * Returns all parameter values.
	 * 
	 * @return array Query, path, and body parameters.
	 */
	public function getParams() {
		return $this->params;
	}
	
	/**
	 * Returns true if a parameter is set.
	 * 
	 * @param string $name Parameter name
	 * @return boolean True if set, otherwise false.
	 */
	public function paramExists($name) {
		return isset($this->params[$name]);
	}
	
	/**
	 * Returns a parameter value
	 * 
	 * @param string $name Parameter name
	 * @return mixed Parameter value.
	 */
	public function getParam($name) {
		return isset($this->params[$name]) ? $this->params[$name] : null;
	}
	
	/**
	 * &Alias of getParam()
	 */
	public function param($name) {
		return $this->getParam($name);	
	}
	
	/**
	 * Returns array of parsed headers.
	 * 
	 * @return array HTTP request headers.
	 */
	public function getHeaders() {
		return $this->headers;	
	}
	
	/**
	 * Returns a single HTTP header if set.
	 * 
	 * @param string $name Header name (lowercase).
	 * @return string Header value if set, otherwise null.
	 */
	public function getHeader($name) {
		return isset($this->headers[$name]) ? $this->headers[$name] : null;	
	}
	
	/**
	 * Returns the request cookies.
	 * 
	 * @return array Associative array of cookies sent with request.
	 */
	public function getCookies() {
		return $this->cookies;
	}
	
	/**
	 * Returns the request files.
	 * 
	 * @return array Files from $_FILES superglobal.
	 */
	public function getFiles() {
		return $this->files;
	}
	
	/**
	 * Returns true if given content-type is valid for response.
	 * 
	 * @param string $type Content type.
	 * @return boolean True if allowed, otherwise false.
	 */
	public function isContentTypeAllowed($type) {
		return isset($this->allow_content_types[$type]);
	}
	
	/**
	 * Returns array of allowed content types.
	 * 
	 * @return array Indexed array of allowed content types.
	 */
	public function getAllowedContentTypes() {
		return array_keys($this->allow_content_types);
	}
	
	/**
	 * Sets the requested content type if valid, for use by Response.
	 * 
	 * @param string $type Content type requested.
	 * @return boolean True if valid, otherwise false.
	 */
	public function setContentType($type) {
		
		if (isset($this->allow_content_types[$type])) {
			$this->content_type = $type;
			return true;
		}
		
		return false;
	}
	
	/**
	 * Whether request is a XML HTTP request.
	 * 
	 * @return boolean True if XMLHttpRequest, otherwise false.
	 */
	public function isXhr() {
		return isset($this->headers['x-requested-with'])
			&& 'XMLHttpRequest' === $this->headers['x-requested-with'];
	}
	
	/**
	 * Boolean method/xhr checker.
	 * 
	 * @param string $thing Method name or 'XHR' or 'AJAX'.
	 * @return boolean True if request is given thing, otherwise false.
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
	public function isGet() {
		return $this->is(Http::METHOD_GET);
	}
	
	/** Am I a POST request? */
	public function isPost() {
		return $this->is(Http::METHOD_POST);
	}
	
	/**  Am I a PUT request? */
	public function isPut() {
		return $this->is(Http::METHOD_PUT);
	}
	
	/** Am I a HEAD request? */
	public function isHead() {
		return $this->is(Http::METHOD_HEAD);
	}
	
	/**
	 * Allow HTTP method override via header.
	 * 
	 * @return $this
	 */
	public function allowMethodOverrideHeader() {
		$this->allow_method_override_header = true;
		return $this;
	}
	
	/**
	 * Disallow HTTP method override via header.
	 * 
	 * @return $this
	 */
	public function disallowMethodOverrideHeader() {
		$this->allow_method_override_header = false;
		return $this;
	}
	
	/**
	 * Allow HTTP method override via query param.
	 * 
	 * @return $this
	 */
	public function allowMethodOverrideParameter() {
		$this->allow_method_override_parameter = true;
		return $this;
	}
	
	/**
	 * Disallow HTTP method override via query param.
	 * 
	 * @return $this
	 */
	public function disallowMethodOverrideParameter() {
		$this->allow_method_override_parameter = false;
		return $this;
	}
	
	/**
	 * Sets whether to globalize the request params to $_REQUEST.
	 * 
	 * @param boolean $value True or false
	 * @return $this
	 */
	public function setGlobalize($value) {
		$this->globalize = (bool)$value;
		return $this;
	}
	
}
