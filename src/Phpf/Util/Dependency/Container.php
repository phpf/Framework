<?php

namespace Phpf\Util\Dependency;

use Exception;
use Closure;

class Resource {
	
	protected $class;
	
	protected $resource;
	
	public function __construct($resource) {
			
		if (! is_callable($resource)) {
			throw new InvalidArgumentException("Resource must be callable.");
		}
		
		$this->resource = $resource;
	}
	
	public function __invoke(array $args = array()) {
		
		$refl = new \Phpf\Util\Reflection\Callback($this->resource);
		
		try {
			$refl->reflectParameters($args);
		} catch (\Phpf\Util\Reflection\Exception\MissingParam $e) {
			throw new \RuntimeException("Cannot get dependency - missing param {$e->__toString()}.");
		}
		
		return $refl->invoke();
	}
	
}

class Factory extends Resource {
	
	public function __invoke(array $args = array()) {
		return call_user_func_array($this->resource, $args);
	}
}

class Container {
	
	protected $objects;
	
	protected $factories;
	
	protected $resources;
	
	public function __construct(){
		$this->objects = array();
		$this->factories = array();
		$this->resources = array();
	}
	
	public function register($name, $resource) {
		
		if (! $resource instanceof Resource) {
			$resource = new Resource($resource);
		}
		
		$this->resources[$name] = $resource;
		
		return $this;
	}
	
	public function inject($name, $object) {
		
		if (! is_object($object)) {
			trigger_error('Must pass object as second parameter to inject() - '. gettype($object) .' given.');
			return null;
		}
		
		$this->objects[$name] = $object;
		
		return $this;
	}
	
	public function factory($name, \Closure $callback) {
		$this->factories[$name] = $callback;
		return $this;
	}
	
	public function resolve($resource, array $args = array()) {
		
		if (isset($this->objects[$resource])) {
			return $this->objects[$resource];
		}
		
		if (isset($this->resources[$resource])) {
			$res = $this->resources[$resource];
			return $res($args);
		}
		
		if (isset($this->factories[$resource])) {
			$factory = $this->factories[$resource];
			return $factory($args);
		}
		
		if (class_exists($resource, true)) {
			return new $resource($args);
		}
		
		throw new \RuntimeException("Could not resolve dependency $resource.");
	}
	
	public function set( $id, $value, $asSingleton = false ){
		
		if (! is_object($value)) {
			$msg = "Must pass closure or object as value to set() - " . gettype($value) . " given.";
			throw new InvalidArgumentException($msg);
		}
		
		if ( $value instanceof Closure ){
			$this->closures[$id] = $value;
		} elseif ( $asSingleton ){
			$this->singletons[$id] = $value;
		} else {
			$this->objects[$id] = $value;
		}
		
		if ( $asSingleton ){
			$this->singletonIds[] = $id;
		}
	}
	
	public function get( $id, $args = array(), $asSingleton = false ){
		
		if ( $asSingleton ){
				
			if ( ! isset($this->singletons[$id]) ){
					
				if ( ! isset($this->closures[$id]) ){
					throw new Exception("Unknown singleton $id");
					return null;
				}
				
				$this->singletons[$id] = call_user_func_array($this->closures[$id], (array) $args);
			}
			
			return $this->singletons[$id];
		}
		
		if ( isset($this->objects[$id]) )
			return $this->objects[$id];
		
		if ( isset($this->closures[$id]) )
			return call_user_func_array($this->closures[$id], (array) $args);
		
		throw new Exception("Unknown resource $id.");
	}
	
	public function setSingleton( $id, $value ){
		$this->set($id, $value, true);
	}
	
	public function getSingleton($id, $args = array()){
		return $this->get($id, $args, true);
	}
	
	public function singleton( $id, $args = array() ){
		return $this->get($id, $args, true);
	}
	
	public function singletonExists( $id ){
		return in_array($id, $this->singletonIds);
	}

	public function instanceExists( $id ){
			
		if ( $this->singletonExists($id) )
			return isset($this->singletons[$id]);
		
		return isset($this->objects[$id]);
	}
}
