<?php
/**
 * @package Phpf.Http
 */

namespace Phpf\Http;

class Http
{

	/**
	 * HTTP method GET
	 * @var string
	 */
	const METHOD_GET = 'GET';

	/**
	 * HTTP method POST
	 * @var string
	 */
	const METHOD_POST = 'POST';

	/**
	 * HTTP method HEAD
	 * @var string
	 */
	const METHOD_HEAD = 'HEAD';

	/**
	 * HTTP method PUT
	 * @var string
	 */
	const METHOD_PUT = 'PUT';

	/**
	 * HTTP method DELETE
	 * @var string
	 */
	const METHOD_DELETE = 'DELETE';

	/**
	 * HTTP method OPTIONS
	 * @var string
	 */
	const METHOD_OPTIONS = 'OPTIONS';

	/**
	 * HTTP method PATCH
	 * @var string
	 */
	const METHOD_PATCH = 'PATCH';

	/**
	 * Returns HTTP request headers as array.
	 */
	public static function requestHeaders(array $server = null) {
		return Request\Headers::getAll($server);
	}

	/**
	 * Returns a single HTTP request header if set.
	 */
	public static function requestHeader($name, array $server = null) {
		return Request\Headers::get($name, $server);
	}

	/**
	 * Whether using SSL
	 */
	public static function isSsl() {

		if (isset($_SERVER['HTTPS']) && ('on' == strtolower($_SERVER['HTTPS']) || '1' == $_SERVER['HTTPS'])) {
			return true;
		} elseif (isset($_SERVER['SERVER_PORT']) && '443' == $_SERVER['SERVER_PORT']) {
			return true;
		}

		return false;
	}

	/**
	 * Returns server HTTP protocol string.
	 */
	public static function serverProtocol() {

		$protocol = $_SERVER['SERVER_PROTOCOL'];

		if ('HTTP/1.1' != $protocol && 'HTTP/1.0' != $protocol)
			$protocol = 'HTTP/1.0';

		return $protocol;
	}

	/**
	 * Set HTTP status header.
	 *
	 * @param int $code HTTP status code.
	 */
	public static function statusHeader($code) {
		$description = self::statusHeaderDesc($code);

		if (empty($description))
			return;

		$protocol = self::serverProtocol();

		return "$protocol $code $description";
	}

	/**
	 * Gets the header information to prevent caching.
	 *
	 * The several different headers cover the different ways cache prevention is
	 * handled
	 * by different browsers
	 *
	 * @return array The associative array of header names and field values.
	 */
	public static function nocacheHeaders() {

		$headers = array(
			'Expires' => 'Wed, 11 Jan 1984 05:00:00 GMT', 
			'Cache-Control' => 'no-cache, must-revalidate, max-age=0', 
			'Pragma' => 'no-cache',
		);

		$headers['Last-Modified'] = false;
		return $headers;
	}

	public static function cacheHeaders($expires_offset = 86400) {

		$headers = array();

		if (0 == $expires_offset || ! is_numeric($expires_offset)) {
			$headers['Cache-Control'] = 'no-cache, must-revalidate, max-age=0';
			$headers['Expires'] = 'Thu, 19 Nov 1981 08:52:00 GMT';
			$headers['Pragma'] = 'no-cache';

		} else {
			$headers['Cache-Control'] = "Public, max-age=$expires_offset";
			$headers['Expires'] = gmdate('D, d M Y H:i:s', time() + $expires_offset).' GMT';
			$headers['Pragma'] = 'Public';
		}

		return $headers;
	}

	/**
	 * Retrieve the description for the HTTP status.
	 *
	 * @param int $code HTTP status code.
	 * @return string Empty string if not found, or description if found.
	 */
	public static function statusHeaderDesc($code) {

		$code = abs(intval($code));

		$header_to_desc = array(100 => 'Continue', 101 => 'Switching Protocols', 102 => 'Processing', 200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 207 => 'Multi-Status', 226 => 'IM Used', 300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 306 => 'Reserved', 307 => 'Temporary Redirect', 400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Timeout', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Long', 415 => 'Unsupported Media Type', 416 => 'Requested Range Not Satisfiable', 417 => 'Expectation Failed', 422 => 'Unprocessable Entity', 423 => 'Locked', 424 => 'Failed Dependency', 426 => 'Upgrade Required', 500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Timeout', 505 => 'HTTP Version Not Supported', 506 => 'Variant Also Negotiates', 507 => 'Insufficient Storage', 510 => 'Not Extended');

		return isset($header_to_desc[$code]) ? $header_to_desc[$code] : '';
	}

}
