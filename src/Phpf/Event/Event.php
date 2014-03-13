<?php

namespace Phpf\Event;

class Event implements \ArrayAccess {
	
	public $id;
	
	private $defaultPrevented = false;
	
	private $propagationStopped = false;
	
	final public function __construct( $id ){
		$this->id = $id;
	}
	
	final public function preventDefault(){
		$this->defaultPrevented = true;
		return $this;
	}
	
	final public function isDefaultPrevented(){
		return $this->defaultPrevented;
	}
	
	final public function stopPropagation(){
		$this->propagationStopped = true;
		return $this;
	}
	
	final public function isPropagationStopped(){
		return $this->propagationStopped;
	}
	
	public function offsetGet( $index ){
		return $this->$index;
	}
	
	public function offsetSet( $index, $newval ){
		$this->$index = $newval;
	}
	
	public function offsetExists( $index ){
		return isset($this->$index);
	}
	
	public function offsetUnset( $index ){
		unset($this->$index);
	}
	
}
