<?php

namespace Phpf\Util;

class DataContainer implements \ArrayAccess, \Countable, iContainer {
	
	protected $data = array();
	
	public function __set($var, $val){
		$this->data[$var] = $val;
	}
	
	public function __get($var){
		return isset($this->data[$var]) ? $this->result($this->data[$var]) : null;
	}
	
	public function __isset($var){
		return isset($this->data[$var]);
	}
	
	public function __unset($var){
		unset($this->data[$var]);
	}
	
	public function set( $var, $val ){
		$this->__set($var, $val);
		return $this;
	}
	
	public function get( $var ){
		return $this->__get($var);
	}
	
	public function exists($var){
		return $this->__isset($var);
	}
	
	public function remove($var){
		$this->__unset($var);
		return $this;
	}
	
	public function offsetGet( $index ){
		return $this->__get($index);
	}
	
	public function offsetSet( $index, $newval ){
		$this->__set($index, $newval);
	}
	
	public function offsetExists( $index ){
		return $this->__isset($index);
	}
	
	public function offsetUnset( $index ){
		$this->__unset($index);
	}
	
	public function count(){
		return count($this->data);
	}
	
	public function setData( array $data ){
		$this->data = $data;
		return $this;
	}
	
	public function addData( array $data ){
		$this->data = array_merge($this->data, $data);
		return $this;
	}
	
	public function getData(){
		return $this->data;
	}
	
	/**
	 * Imports an array or object containing data as properties.
	 */
	public function import( $data ){
		
		if (!is_array($data) && !$data instanceof \Traversable){
			$data = (array) $data;
		}
		
		foreach($data as $k => $v){
			$this->__set($k, $v);
		}
		
		return $this;
	}
	
	/**
	 * Returns data array.
	 */
	public function toArray(){
		return $this->data;
	}
	
	/**
	 * Executes callable properties - i.e. closures or invokable objects.
	 * Hence, methods can be attached as properties (like JavaScript)
	 */
	public function __call($fn, $params) {
		
		if (isset($this->data[$fn]) && is_callable($this->data[$fn])) {
			return $this->invoke($this->data[$fn], $params);
		}
		
		trigger_error("Unknown method '$fn'.", E_USER_NOTICE);
	}
	
	/**
	 * If value is a closure, executes it before returning.
	 */
	protected function result($var) {
		return ($var instanceof \Closure) ? $var() : $var;
	}
	
	/**
	 * Invokes a callback, which can be a closure, invokable object, or function,
	 * with the given arguments.
	 */
	protected function invoke($callback, array $args = array()) {
		switch(count($args)) {
			case 0 :
				return $callback();
			case 1 :
				return $callback($args[0]);
			case 2 :
				return $callback($args[0], $args[1]);
			case 3 :
				return $callback($args[0], $args[1], $args[2]);
			case 4 :
				return $callback($args[0], $args[1], $args[2], $args[3]);
			default :
				return call_user_func_array($callback, $args);
		}
	}
		
}
