<?php

namespace Phpf\App;

use ArrayAccess;
use Countable;
use RuntimeException;
use Phpf\Util\iManager;

class App implements ArrayAccess, Countable {
	
	public $namespace;
	
	public $config;
	
	protected $aliaser;
	
	protected $components = array();
	
	protected static $instance;
	
	/**
	 * Returns object instance.
	 * If not set, throws RuntimeException
	 */
	public static function instance(){
			
		if (! isset(static::$instance)) {
			throw new RuntimeException("No application set.");
		}
		
		return static::$instance;
	}
	
	/**
	 * Create a new application instance.
	 * 
	 * @param array|ArrayAccess $config Configuration array/object.
	 * @return \Application
	 */
	public static function create( $config ){
		
		if (isset(static::$instance)) {
			throw new RuntimeException('Application already exists.');
		}
		
		if (! is_array($config) && ! $config instanceof ArrayAccess) {
			throw new RuntimeException("Config must be array of instance of ArrayAccess.");
		}
		
		// create new instance
		$app = static::$instance = new static();
		
		// set config object
		$app->set('config', new \Phpf\Config\Object($config));
		
		// create lazy class aliaser and register (w/ SPL)
		$app->aliaser = new ClassAliaser;
		if (isset($config['aliases'])) {
			$app->aliaser->addAliases($config['aliases']);
		}
		$app->aliaser->register();
		
		// create package function loader for Phpf namespace
		$app->functional = new PackageFunctions('Phpf');
		
		// util should always be available...
		if ( $app->loadFunctions('Util') ){
			// register an autoloader for the app namespace with
			// basepath as root. The autoloader is PSR-0, so it 
			// will convert namespace(s) to directories.
			autoloader_register($app['config']['namespace'], BASEPATH);
		}
		
		return $app;
	}
	
	protected function __construct(){}
	
	/**
	 * Set a component.
	 * 
	 * If $object is a string, the component will be added and 
	 * called as a singleton.
	 * 
	 * @param string $name Case-sensitive component name.
	 * @param object|string $object Component object or classname.
	 * @return $this
	 */
	public function set( $name, $object ){
		
		if (is_string($object)) {
			return $this->setSingleton($name, $object);
		} 
		
		if (! is_object($object)) {
			trigger_error(
				'Non-singleton components should be objects - '
				.gettype($object).' given for '.$name
			);
			return null;
		}
		
		$this->components[$name] = $object;
		
		return $this;
	}
	
	/**
	 * Set a singleton component.
	 * 
	 * The given class must be fully resolved (or aliased) and
	 * it must have a static instance() method that returns the object.
	 * 
	 * @param string $name Case-sensitive component name.
	 * @param string $class Component singleton class.
	 * @return $this
	 */
	public function setSingleton( $name, $class ){
		
		if (! method_exists($class, 'instance')) {
			trigger_error(
				'Classes implementing singleton must have method "instance()" - Class "'
				.(is_object($class) ? get_class($class) : $class) . '" does not.'
			);
			return null;
		}
		
		if (is_object($class)) {
			$class = get_class($class);
		}
		
		$this->components[$name] = $class;
		
		return $this;
	}
	
	/**
	 * Returns a component.
	 * 
	 * @param string $name Case-sensitive component name.
	 * @return object Component object if exists, otherwise null.
	 */
	public function get( $name ){
		
		if (! isset($this->components[$name]))
			return null;
		
		if (is_object($this->components[$name]))
			return $this->components[$name];
		
		$class = $this->components[$name];
		
		return $class::instance();
	}
	
	/**
	 * Sets the application namespace.
	 * 
	 * @param string $namespace Case-sensitive application namespace.
	 * @return $this
	 */
	public function setNamespace( $namespace ){
		$this->namespace = trim($namespace, '\\');
		return $this;
	}
	
	/**
	 * Returns the application namespace.
	 * 
	 * @return string Application namespace
	 */
	public function getNamespace(){
		return $this->namespace;
	}
	
	/**
	 * Creates a class alias.
	 * 
	 * @param string $from Fully resolved class to alias.
	 * @param string $to The class alias.
	 * @return $this
	 */
	public function alias( $from, $to ){
		$this->aliaser->alias($from, $to);
		return $this;
	}
	
	/**
	 * Loads functions for a package.
	 * 
	 * Package should be in the top-level namespace 
	 * set in the create() method.
	 * 
	 * @param string Package name
	 * @return boolean Whether functions were loaded.
	 */
	public function loadFunctions( $package ){
		return $this->functional->load($package);
	}
	
	/**
	 * Returns true if functions are loaded for a package,
	 * otherwise returns false.
	 * 
	 * @param string Package name
	 * @return boolean True if functions loaded, otherwise false.
	 */
	public function functionsLoaded( $package ){
		return $this->functional->loaded($package);
	}
	
	/**
	 * Shortcut method combining the two above.
	 */
	public function functions( $package ){
			
		if (! $this->functionsLoaded($package)) {
			return $this->loadFunctions($package);
		}
		
		return true;
	}
	
	/**
	 * Sets cache driver based on config values, or lack thereof.
	 * @return \Cache
	 */
	public function startCache(){
		
		if (! $cache = $this->get('cache')) {
			throw new RuntimeException("Cache not set.");
		}
		
		$config = $this->get('config');	
		$cacheConf = $config['cache'];
		
		// set cache driver if not exists
		if (! isset($cacheConf['driver'])) {
			$cacheConf['driver'] = 'static';	
			$cacheConf['driver-class'] = 'StaticDriver';
		}
		
		if (isset($cacheConf['driver-class'])) {
			$cacheClass = $cacheConf['driver-class'];
		} else {
			$cacheClass = 'Phpf\\Cache\\Driver\\'.ucfirst($cacheConf['driver']).'Driver';
		}
		
		if (class_exists($cacheClass)) {
			$cache->setDriver(new $cacheClass);
		} else {
			$cache->setDriver(new \Phpf\Cache\Driver\StaticDriver);
		}
		
		return $cache;
	}
	
	/**
	 * Route the current request.
	 */
	public function routeRequest(){
		return $this->get('router')->dispatch($this['request'], $this['response']);
	}
	
	/**
	 * ArrayAccess
	 */
	public function offsetGet( $index ){
		return $this->get($index);
	}
	
	/**
	 * ArrayAccess
	 */
	public function offsetSet( $index, $newval ){
		$this->set($index, $newval);
	}
	
	/**
	 * ArrayAccess
	 */
	public function offsetUnset( $index ){
		trigger_error('Cannot unset application components.');
	}
	
	/**
	 * ArrayAccess
	 */
	public function offsetExists( $index ){
		return array_key_exists($index, $this->components);
	}
	
	/**
	 * Countable
	 */
	public function count(){
		return count($this->components);
	}
	
	/**
	 * Returns a component that matches the called method.
	 */
	public function __call( $func, $args ){
		
		if (isset($this->components[$func])) {
			return $this->get($func);
		}
	}
	
}
