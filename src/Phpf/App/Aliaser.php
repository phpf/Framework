<?php

namespace Phpf\App;

class Aliaser
{

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
	
	/**
	 * Constructor
	 * @return $this
	 */
	public function __construct() {
		$this->registered = false;
		$this->aliases = array();
		$this->resolved = array();
	}
	
	/**
	 * Add a class alias
	 * 
	 * @param string $from Full resolved classname to alias.
	 * @param string $to Alias to assign to class.
	 * @return $this
	 */
	public function alias($from, $to) {
		$this->aliases[$to] = $from;
		return $this;
	}
	
	/**
	 * Adds array of class aliases.
	 * 
	 * @param array $aliases Key-value pairs of $alias => $class
	 */
	public function addAliases(array $aliases) {
		foreach ( $aliases as $alias => $class ) {
			$this->alias($class, $alias);
		}
		return $this;
	}
	
	/**
	 * Returns the alias for a class.
	 * 
	 * @param string $class Fully resolved class name.
	 * @return string|false Alias if exists, otherwise false.
	 */
	public function getAlias($class) {
		return array_search($class, $this->aliases, true);
	}
	
	/**
	 * Returns true if given class has an alias.
	 * 
	 * @param string $class Fully resolved class name.
	 * @return boolean True if class has alias registered, otherwise false.
	 */
	public function hasAlias($class) {
		return (bool) $this->getAlias($class);
	}
	
	/**
	 * Returns true if given class name is an alias.
	 * 
	 * @param string $class Class to check if alias.
	 * @return boolean True if given class is alias, otherwise false.
	 */
	public function isAlias($class) {
		return isset($this->aliases[$class]);
	}
	
	/**
	 * Returns a fully resolved class name, given an alias.
	 * 
	 * @param string $class An alias to resolve.
	 * @return string|null Resolved class name, or null on failure.
	 */
	public function resolve($class) {
		return isset($this->aliases[$class]) ? $this->aliases[$class] : null;
	}
	
	/**
	 * Register as SPL autoloader.
	 * Enables aliases to be set only when needed 
	 * (and hence, avoid unnecessary autoloading).
	 * 
	 * @return $this
	 */
	public function register() {
		spl_autoload_register(array($this, 'load'));
		$this->registered = true;
		return $this;
	}

	/**
	 * Unregister the SPL autoloader.
	 * 
	 * @return $this
	 */
	public function unregister() {
		spl_autoload_unregister(array($this, 'load'));
		$this->registered = false;
		return $this;
	}
	
	/**
	 * Returns true if registered as SPL autoloader, otherwise false.
	 * 
	 * @return boolean
	 */
	public function isRegistered() {
		return $this->registered;
	}
	
	/**
	 * SPL autoload callback to lazily declare class aliases.
	 * 
	 * @param string $alias Class alias
	 * @return void
	 */
	protected function load($alias) {

		if (null !== $class = $this->resolve($alias)) {

			if (class_exists($class, true)) {

				class_alias($class, $alias);

				$this->resolved[$class] = $alias;
			}
		}
	}

}
