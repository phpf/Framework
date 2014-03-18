<?php

namespace Phpf\App;

class ClassAliaser {
	
	/**
	 * Array of 'alias' => 'classname'
	 * @var array
	 */
	protected $aliases;
	
	/**
	 * Whether registered with spl_autoload_register
	 * @var boolean
	 */
	protected $registered;
	
	/**
	 * Resolved classes
	 * @var array
	 */
	public $resolved;
	
	public function __construct(){
		$this->registered = false;
		$this->aliases = array();
		$this->resolved = array();
	}
	
	public function alias( $from, $to ){
		$this->aliases[$to] = $from;
		return $this;
	}
	
	public function register(){
		spl_autoload_register(array($this, 'load'));
		$this->registered = true;
		return $this;
	}
	
	public function unregister(){
		spl_autoload_unregister(array($this, 'load'));
		$this->registered = false;
		return $this;
	}
	
	public function isRegistered(){
		return $this->registered;
	}
	
	public function addAliases( array $aliases ) {
		foreach($aliases as $alias => $class) {
			$this->alias($class, $alias);
		}
		return $this;
	}
	
	public function resolve( $class ){
		return isset($this->aliases[$class]) ? $this->aliases[$class] : null;
	}
	
	protected function load( $alias ){
		
		if ( null !== $class = $this->resolve($alias) ){
		
			if ( class_exists($class) ){
		
				class_alias($class, $alias);
		
				$this->resolved[$class] = $alias;
			}
		}
	}
	
}