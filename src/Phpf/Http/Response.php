<?php
/**
 * @package Phpf.Http
 */

namespace Phpf\Http;

use Phpf\Http\Http;

class Response {

	/**
	 * Content type to use if no other valid type is requested.
	 * @var boolean
	 */
	const DEFAULT_CONTENT_TYPE = 'text/html';

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
	 * Whether the output buffer has been started.
	 * Will not start a new one if true, but still calls ob_end_flush()
	 * @var boolean
	 */
	protected $buffer_started;
	
	/**
	 * Whether to gzip the response body.
	 * @var boolean
	 */
	protected $gzip;

	/**
	 * The output content type.
	 * @var string
	 */
	protected $content_type;

	/**
	 * Associative array of permitted content types.
	 * @var array
	 */
	protected $content_types = array(
		'html' => 'text/html', 
		'json' => 'application/json', 
		'jsonp' => 'text/javascript', 
		'xml' => 'text/xml', 
	);

	/**
	 * Sets up Response using some data from the Request.
	 */
	public function __construct( Request $request ) {

		if (Request\Headers::acceptEncoding('gzip', $request->headers) 
			&& extension_loaded('zlib')) 
		{
			$this->gzip = true;
		} else {
			$this->gzip = false;
		}

		if ($request->isXhr()) {
			$this->setCacheHeaders(false);
			$this->setContentTypeOptionsHeader('nosniff');
			$this->setFrameOptionsHeader('deny');
		}

		// first try to set content type using parameter, then try using header
		if (! isset($request->content_type) 
			|| ! $this->maybeSetContentType($request->content_type)) 
		{
			if ($type = Request\Headers::accept($this->content_types, $request->headers)) {
				$this->content_type = array_search($type, $this->content_types);
			}
		}
	}

	/**
	 * Send the response headers and body.
	 */
	public function send() {
		
		header_remove(); // remove all headers
		
		// send at least some cache header
		if (! isset($this->headers['Cache-Control'])) {
			$this->nocache();
		}

		// Status header
		$this->sendStatusHeader();

		// Content-Type header
		$this->sendContentTypeHeader();
		
		// Rest of headers
		foreach ( $this->headers as $name => $value ) {
			header(sprintf("%s: %s", $name, $value), true);
		}
		
		// Output the body
			
		if (! $this->buffer_started) {
		#	if (! $this->gzip || ! ob_start('ob_gzhandler'))
				ob_start();
		}
		
		echo $this->body;

		ob_end_flush();

		exit;
	}

	/**
	 * Sets the body content.
	 */
	public function setBody( $value, $overwrite = true ) {

		if ( $overwrite || empty($this->body) ) {

			if (is_object($value) && ! $value = $this->objectStr($value)) {
				trigger_error('Cannot set object as body - no __toString() method.', E_USER_NOTICE);
				return $this;
			}

			$this->body = $value;
		}

		return $this;
	}

	/**
	 * Adds content to the body.
	 */
	public function addBody( $value, $how = 'append' ) {

		if (is_object($value) && ! $value = $this->objectStr($value)) {
			trigger_error('Cannot set object as body - no __toString() method.', E_USER_NOTICE);
			return $this;
		}

		if ('prepend' === $how || 'before' === $how) {
			$this->body = $value . $this->body;
		} else {
			$this->body .= $value;
		}

		return $this;
	}
	
	/**
	 * Returns body string.
	 */
	public function getBody(){
		return $this->body;
	}
	
	/**
	 * Sets output charset
	 */
	public function setCharset( $charset ) {
		$this->charset = $charset;
		return $this;
	}

	/**
	 * Returns output charset
	 */
	public function getCharset() {
		return $this->charset;
	}

	/**
	 * Sets the HTTP response status code.
	 */
	public function setStatus( $code ) {
		$this->status = (int)$code;
		return $this;
	}

	/**
	 * Sets the content type.
	 */
	public function setContentType( $type ) {
		$this->content_type = $type;
		return $this;
	}
	
	/**
	 * Sets the buffer as started.
	 */
	public function setBufferStarted() {
		$this->buffer_started = true;
		return $this;
	}
	
	/**
	 * Returns true if given response content-type/media type is known.
	 */
	public function isKnownContentType( $type ) {
		return isset($this->content_types[$type]);
	}

	/**
	 * Sets $content_type, but only if $type is a valid content type.
	 */
	public function maybeSetContentType( $type ) {

		if (isset($this->content_types[$type])) {
			$this->setContentType($type);
			return true;
		}
		
		return false;
	}

	/**
	 * Sets a header. Replaces existing by default.
	 */
	public function setHeader( $name, $value, $overwrite = true ) {
		
		if ($overwrite || ! isset($this->headers[$name])) {
			$this->headers[$name] = $value;
		}

		return $this;
	}

	/**
	 * Sets array of headers.
	 */
	public function setHeaders( array $headers, $overwrite = true ) {

		foreach ( $headers as $name => $value ) {
			$this->setHeader($name, $value, $overwrite);
		}

		return $this;
	}

	/**
	 * Adds a header. Does not replace existing.
	 */
	public function addHeader( $name, $value ) {
		return $this->setHeader($name, $value, false);
	}

	/**
	 * Adds array of headers.
	 */
	public function addHeaders( array $headers ) {
		$this->setHeaders($headers, false);
	}

	/**
	 * Returns assoc. array of currently set headers.
	 */
	public function getHeaders() {
		return $this->headers;
	}

	/**
	 * Sets the various cache headers. Auto unsets 'Last-Modified'
	 * if $expires_offset is 0.
	 * 
	 * @param int|bool $expires_offset	Time in seconds from now to cache. 
	 * 									Pass 0 or false for no cache.
	 */
	public function setCacheHeaders( $expires_offset = 86400 ) {

		$headers = Http::cacheHeaders($expires_offset);

		if (empty($expires_offset)) {
			header_remove('Last-Modified');
			unset($this->headers['Last-Modified']);
		}

		$this->addHeaders($headers);

		return $this;
	}

	/**
	 * Sets the "X-Frame-Options" header.
	 */
	public function setFrameOptionsHeader( $value ) {

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
	 * Sets the "X-Content-Type-Options" header.
	 */
	public function setContentTypeOptionsHeader( $value ) {
		return $this->setHeader('X-Content-Type-Options', $value);
	}

	/**
	 * Sets no cache headers.
	 */
	public function nocache() {
		return $this->setCacheHeaders(false);
	}

	/**
	 * Sets 'X-Frame-Options' header to 'DENY'.
	 */
	public function denyIframes() {
		return $this->setFrameOptionsHeader('DENY');
	}

	/**
	 * Sets 'X-Frame-Options' header to 'SAMEORIGIN'.
	 */
	public function sameoriginIframes() {
		return $this->setFrameOptionsHeader('SAMEORIGIN');
	}

	/**
	 * Sets 'X-Content-Type-Options' header to 'nosniff'.
	 */
	public function nosniff() {
		return $this->setContentTypeOptionsHeader('nosniff');
	}

	/**
	 * Sends the status header.
	 */
	public function sendStatusHeader() {

		if (! isset($this->status)) {
			if (isset($GLOBALS['HTTP_RESPONSE_CODE'])) {
				$this->status = $GLOBALS['HTTP_RESPONSE_CODE'];
			} elseif (isset($this->headers['Location'])) {
				$this->status = 307;
			} else {
				$this->status = 200; // assume success
			}
		}
		
		$code = $this->status;
		$desc = Http::statusHeaderDesc($code);
		$protocol = Http::serverProtocol();
		
		header(sprintf("%s %d %s", $protocol, $code, $desc), false, $code);
		
		header(sprintf("Status: %d %s", $code, $desc)); // send extra "Status" header
		
		return $this;
	}

	/**
	 * Sends the 'Content-Type' header.
	 */
	public function sendContentTypeHeader() {

		if (isset($this->content_type)) {
			$type = $this->content_types[$this->content_type];
		} else {
			$type = self::DEFAULT_CONTENT_TYPE;
		}

		header(sprintf("Content-Type: %s; charset=%s", $type, $this->getCharset()), true);

		return $this;
	}

	/**
	 * Alias for setBody()
	 * @see Request\Response::setBody()
	 */
	public function setContent( $value ) {
		return $this->setBody($value);
	}

	/**
	 * Alias for addBody()
	 * @see Request\Response::addBody()
	 */
	public function addContent( $value, $how = 'append' ) {
		return $this->addBody($value, $how);
	}

	/**
	 * Returns string if object has __toString() method, otherwise null.
	 */
	protected function objectStr( $object ) {

		if (method_exists($object, '__toString')) {
			return $object->__toString();
		}

		return null;
	}

}
