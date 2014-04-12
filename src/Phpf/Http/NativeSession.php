<?php

namespace Phpf\Http;

use ArrayAccess;
use Countable;

class NativeSession implements SessionInterface, ArrayAccess, Countable {
	
	/**
	 * If given array of cookie parameters, sets cookie parameters.
	 */
	public function __construct(array $cookieparams = array()) {
			
		ini_set('session.use_cookies', 1);
		
		$this->cookie_params = array_replace(array(
			'lifetime' => 86400*7,
			'path' => '/',
			'domain' => '.'.rtrim($_SERVER['HTTP_HOST'], '/\\').rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\'),
			'secure' => false,
			'httponly' => false,
		), $cookieparams);
		
	}
	
	/**
	 * Starts the session
	 * @return boolean Result of session_start()
	 */
	public function start() {
			
		$started = session_start();
		
		if ($started && isset($this->cookie_params)) {
			setcookie(
				$this->getName(), 
				$this->getId(), 
				time() + $this->cookie_params['lifetime'], 
				$this->cookie_params['path'], 
				$this->cookie_params['domain'], 
				$this->cookie_params['secure'], 
				$this->cookie_params['httponly']
			);
		}
		
		return $started;
	}
	
	/**
	 * Whether session is started.
	 * 
	 * @return boolean True if session started, otherwise false.
	 */
	public function isStarted() {
		return '' !== session_id();
	}
	
	/**
	 * Returns session ID.
	 * 
	 * @return string Result of session_id()
	 */
	public function getId() {
		return session_id();
	}
	
	/**
	 * Sets session ID. Must be called before start().
	 * 
	 * @param string $id ID to use for current session.
	 * @return string Session ID
	 * @throws RuntimeException if session has been started.
	 */
	public function setId($id) {
		
		if ($this->isStarted()) {
			throw new RuntimeException("Cannot set ID - session already started.");
		}
		
		return session_id($id);
	}
	
	/**
	 * Returns session name.
	 * 
	 * @return string Session name
	 */
	public function getName() {
		return session_name();
	}
	
	/**
	 * Sets session name.
	 * 
	 * @param string $name Name to set as session name.
	 * @return string Name of current session.
	 */
	public function setName($name) {
		return session_name($name);
	}
	
	/**
	 * Returns a session variable.
	 * 
	 * @param string $var Name of session variable.
	 * @param mixed $default Value to return if session variable does not exist.
	 */
	public function get($var, $default = null) {
		return $this->exists($var)
			? $_SESSION[$var]
			: $default;
	}
	
	/**
	 * Sets a session variable.
	 * 
	 * @param string $var Name of session variable.
	 * @param mixed $val Value of session variable.
	 * @return $this
	 */
	public function set($var, $val) {
		$_SESSION[$var] = $val;
		return $this;
	}
	
	/**
	 * Returns true if a session variable exists, otherwise false.
	 * 
	 * @param string $var Name of session variable.
	 * @return boolean Whether session variable exists.
	 */
	public function exists($var) {
		return isset($_SESSION[$var]) || array_key_exists($var, $_SESSION);
	}
	
	/**
	 * Unsets a session variable.
	 * 
	 * @param string $var Name of session variable to unset.
	 * @return $this
	 */
	public function remove($var) {
		unset($_SESSION[$var]);
		return $this;
	}
	
	/**
	 * Destroys the current session.
	 * 
	 * @return $this
	 */
	public function destroy() {
		
		$_SESSION = array();

		$this->unsetCookie();

		session_unset();

		if (session_id()) {
			session_destroy();
		}
		
		return $this;
	}
	
	/**
	 * Returns session cookie if set.
	 * 
	 * @return string Cookie if set, otherwise null.
	 */
	public function getCookie() {
		return isset($_COOKIE[$this->getName()]) ? $_COOKIE[$this->getName()] : null;
	}
	
	/**
	 * Unsets/invalidates session cookie.
	 * 
	 * @return $this
	 */
	public function unsetCookie() {
			
		unset($_COOKIE[$this->getName()]);
		
		if (isset($this->cookie_params)) {
			$p = $this->cookie_params;
		} else {
			$p = session_get_cookie_params();
		}
		
		setcookie($this->getName(), '', time() - 31536000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
		
		return $this;
	}
	
	/**
	 * @param $index 
	 * @param $newval 
	 * @return void
	 */
	public function offsetSet($index, $newval) {
		$this->set($index, $newval);
	}

	/**
	 * @param $index 
	 * @return mixed
	 */
	public function offsetGet($index) {
		return $this->get($index);
	}

	/**
	 * @param $index 
	 * @return void
	 */
	public function offsetUnset($index) {
		$this->remove($index);
	}

	/**
	 * @param $index 
	 * @return boolean
	 */
	public function offsetExists($index) {
		return $this->exists($index);
	}
	
	/**
	 * @return integer
	 */
	public function count() {
		return count($_SESSION);
	}
	
	// Magic methods for object->property access
	
	public function __get($var) {
		return $this->get($var);
	}
	
	public function __set($var, $val) {
		$this->set($var, $val);
	}
	
	public function __isset($var) {
		return $this->exists($var);
	}
	
	public function __unset($var) {
		$this->remove($var);
	}
	
}
