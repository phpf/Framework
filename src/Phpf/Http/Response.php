<?php
/**
 * @package Phpf.Http
 */

namespace Phpf\Http;

class Response {

	/**
	 * Content type to use if no other valid type is requested.
	 * @var string
	 */
	const DEFAULT_CONTENT_TYPE = 'text/html';

	/**
	 * The output content type.
	 * @var string
	 */
	protected $content_type;

	/**
	 * Charset to use in content-type header.
	 * @var string
	 */
	protected $charset = 'UTF-8';

	/**
	 * HTTP Status code to send.
	 * @var integer
	 */
	protected $status;

	/**
	 * Associative array of headers to send.
	 * @var array
	 */
	protected $headers = array();

	/**
	 * Response body.
	 * @var string
	 */
	protected $body;
	
	/**
	 * Whether to gzip the response body.
	 * @var boolean
	 */
	protected $gzip;
	
	/**
	 * Associative array of permitted content types.
	 * @var array
	 */
	protected $content_types = array(
		'html'	=> 'text/html',
		'xml'	=> 'text/xml',
		'jsonp' => 'text/javascript',
		'json'	=> 'application/json',
	);

	/**
	 * Constructor - sets gzip to false by default.
	 * @return void
	 */
	public function __construct() {
		$this->gzip = false;
	}
	
	/**
	 * Uses Request data to set some properties.
	 * 
	 * @param Request $request Current Request object.
	 * @return $this
	 */
	public function setRequest(Request $request) {
		
		// first try to set content type using parameter (if set)
		if (! isset($request->content_type) || ! $this->maybeSetContentType($request->content_type)) {
			// now try using header (if set)
			$this->content_type = http_negotiate_content_type(array_values($this->content_types));
		}
		
		// shall we gzip?
		if (http_in_request_header('accept-encoding', 'gzip') && extension_loaded('zlib')) {
			$this->gzip = true;
		}
		
		// For XHR/AJAX requests, don't cache response, nosniff, and deny iframes
		if ($request->isXhr()) {
			$this->setCacheHeaders(false);
			$this->nosniff();
			$this->denyIframes();
		}
		
		return $this;
	}

	/**
	 * Send the response headers and body.
	 * @return void
	 */
	public function send() {
		
		// (maybe) start output buffering
		if (1 >= ob_get_level()) {
			#if (! $this->gzip || ! ob_start('ob_gzhandler'))
				ob_start();
		}
		
		// send at least some cache header
		if (! isset($this->headers['Cache-Control'])) {
			$this->nocache();
		}

		// Status header
		if (! isset($this->status)) {
			if (isset($GLOBALS['HTTP_RESPONSE_CODE'])) {
				$this->status = $GLOBALS['HTTP_RESPONSE_CODE'];
			} else if (isset($this->headers['Location'])) {
				$this->status = HTTP_REDIRECT_FOUND;
			} else {
				$this->status = 200;
			}
		}
		
		http_send_status($this->status);
		
		// Content-Type header
		if (! isset($this->content_type)) {
			$this->content_type = static::DEFAULT_CONTENT_TYPE;
		}

		http_send_content_type($this->content_type, $this->getCharset());

		// Rest of headers
		foreach ( $this->headers as $name => $value ) {
			header(sprintf("%s: %s", $name, $value), true);
		}
		
		// Output the body
		echo $this->body;

		ob_end_flush();

		exit;
	}

	/**
	 * Sets the body content.
	 * 
	 * @param string|object $value String, or object with __toString() method.
	 * @param string $how How to set the body; one of 'replace' (default), 'append', or 'prepend'.
	 * @return $this
	 */
	public function setBody($value, $how = 'replace') {
			
		if (! is_string($value)) {
			if (method_exists($value, '__toString')) {
				$value = $value->__toString();
			} else {
				trigger_error('Cannot set var as body - given '.gettype($value), E_USER_NOTICE);
				return $this;
			}
		}

		switch(strtolower($how)) {
			case 'replace' :
			default :
				$this->body = $value;
				break;
			case 'append' :
			case 'after' :
				$this->body .= $value;
				break;
			case 'prepend' :
			case 'before' :
				$this->body = $value . $this->body;
				break;
		}
		
		return $this;
	}

	/**
	 * Adds content to the body.
	 * 
	 * @param string|object $value String or object with __toString() method.
	 * @param string $how How to add the body. Default is 'append'.
	 * @return $this
	 */
	public function addBody($value, $how = 'append') {
		return $this->setBody($value, $how);
	}
	
	/**
	 * Returns body string.
	 * 
	 * @return string The body string.
	 */
	public function getBody(){
		return $this->body;
	}
	
	/**
	 * Sets output charset.
	 * 
	 * @param string $charset Charset to send w/ content-type.
	 * @return $this
	 */
	public function setCharset($charset) {
		$this->charset = $charset;
		return $this;
	}

	/**
	 * Returns output charset
	 * 
	 * @return string Charset
	 */
	public function getCharset() {
		return $this->charset;
	}

	/**
	 * Sets the HTTP response status code.
	 * 
	 * @param int $code HTTP status code.
	 * @return $this
	 */
	public function setStatus($code) {
		$this->status = (int)$code;
		return $this;
	}
	
	/**
	 * Returns the response status code.
	 * 
	 * @return int HTTP status code.
	 */
	public function getStatus() {
		return $this->status;
	}
	
	/**
	 * Sets the content type.
	 * 
	 * @param string $type Content-type MIME
	 * @return $this
	 */
	public function setContentType($type) {
		$this->content_type = $type;
		return $this;
	}
	
	/**
	 * Returns true if given response content-type/media type is known.
	 * 
	 * @param string $type Content-type MIME
	 * @return boolean True if valid as response format, otherwise false.
	 */
	public function isContentType($type) {
		return isset($this->content_types[$type]);
	}

	/**
	 * Sets $content_type, but only if $type is a valid content type.
	 * 
	 * @param string $type Content-type MIME
	 * @return boolean True if valid and set, otherwise false.
	 */
	public function maybeSetContentType($type) {

		if (isset($this->content_types[$type])) {
			$this->content_type = $this->content_types[$type];
			return true;
		}
		
		return false;
	}

	/**
	 * Sets a header. Replaces existing by default.
	 * 
	 * @param string $name Header name.
	 * @param string $value Header value.
	 * @param boolean $overwrite Whether to overwrite existing. Default true.
	 * @return $this
	 */
	public function setHeader($name, $value, $overwrite = true) {
		
		if (true === $overwrite || ! isset($this->headers[$name])) {
			$this->headers[$name] = $value;
		}

		return $this;
	}

	/**
	 * Sets array of headers.
	 * 
	 * @param array $headers Associative array of headers to set.
	 * @param boolean $overwrite True to overwrite existing. Default true.
	 * @return $this
	 */
	public function setHeaders(array $headers, $overwrite = true) {

		foreach ( $headers as $name => $value ) {
			$this->setHeader($name, $value, $overwrite);
		}

		return $this;
	}

	/**
	 * Adds a header. Does not replace existing.
	 * 
	 * @param string $name Header name.
	 * @param string $value Header value.
	 * @return $this
	 */
	public function addHeader($name, $value) {
		return $this->setHeader($name, $value, false);
	}

	/**
	 * Adds array of headers. Does not replace existing.
	 * 
	 * @param array Associative array of headers to set.
	 * @return $this
	 */
	public function addHeaders(array $headers) {
		$this->setHeaders($headers, false);
	}

	/**
	 * Returns assoc. array of currently set headers.
	 * 
	 * @return array Associative array of currently set headers.
	 */
	public function getHeaders() {
		return $this->headers;
	}

	/**
	 * Sets the various cache headers. Auto unsets 'Last-Modified'
	 * if $expires_offset is falsy.
	 * 
	 * @param int|bool $expires_offset	Time in seconds from now to cache. 
	 * 									Pass 0 or false for no cache.
	 * @return $this
	 */
	public function setCacheHeaders($expires_offset = 86400) {

		$headers = http_build_cache_headers($expires_offset);
		
		// empty() returns false for zero as string
		if (empty($expires_offset) || '0' === $expires_offset) {
			header_remove('Last-Modified');
			unset($this->headers['Last-Modified']);
		}

		$this->addHeaders($headers);

		return $this;
	}

	/**
	 * Sets the "X-Frame-Options" header.
	 * 
	 * @param string $value One of 'sameorigin'/true or 'deny'/false.
	 * @return $this
	 */
	public function setFrameOptionsHeader($value) {

		switch($value) {
			case 'SAMEORIGIN' :
			case 'sameorigin' :
			case true :
			default :
				$value = 'SAMEORIGIN';
				break;
			case 'DENY' :
			case 'deny' :
			case false :
				$value = 'DENY';
				break;
		}

		return $this->setHeader('X-Frame-Options', $value);
	}

	/**
	 * Sets 'X-Frame-Options' header to 'DENY'.
	 * @return $this
	 */
	public function denyIframes() {
		return $this->setFrameOptionsHeader('DENY');
	}

	/**
	 * Sets 'X-Frame-Options' header to 'SAMEORIGIN'.
	 * @return $this
	 */
	public function sameoriginIframes() {
		return $this->setFrameOptionsHeader('SAMEORIGIN');
	}

	/**
	 * Sets no cache headers.
	 * @return $this
	 */
	public function nocache() {
		return $this->setCacheHeaders(false);
	}

	/**
	 * Sets 'X-Content-Type-Options' header to 'nosniff'.
	 * @return $this
	 */
	public function nosniff() {
		return $this->setHeader('X-Content-Type-Options', 'nosniff');
	}

	/**
	 * &Alias of setBody()
	 * @return $this
	 */
	public function setContent($value) {
		return $this->setBody($value);
	}

	/**
	 * &Alias of addBody()
	 * @return $this
	 */
	public function addContent($value, $how = 'append') {
		return $this->addBody($value, $how);
	}

}
