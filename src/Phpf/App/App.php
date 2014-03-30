<?php

namespace Phpf\App;

use ArrayAccess;
use Countable;
use RuntimeException;
use Phpf\Util\iManager;

class App implements ArrayAccess, Countable {
	
	const DEFAULT_ID = 'app';
	
	protected $components = array();
	
	protected static $instances = array();
	
	/**
	 * Returns object instance.
	 * 
	 * @throws RuntimeException if no instance with given ID
	 * @param string $id Application ID
	 * @return \App
	 */
	public static function instance($id = self::DEFAULT_ID) {
			
		if (! isset(static::$instances[$id])) {
			throw new RuntimeException("No application set.");
		}
		
		return static::$instances[$id];
	}
	
	/**
	 * Default configuration settings.
	 * 
	 * @param array $config User config array.
	 * @return array Configuration settings.
	 */
	public static function configure(array $user_config) {
			
		// Merge user config and defaults
		return $user_config + array(
			'namespace'	=> 'App',
			'charset'	=> 'UTF-8',
			'timezone'	=> 'UTC',
			'debug' 	=> false,
			'dirs' => array(
				'system'	=> 'system',
				'app'		=> 'app',
				'library'	=> 'app/library',
				'module'	=> 'app/modules',
				'views'		=> 'app/views',
				'assets'	=> 'app/public',
				'scripts'	=> 'app/scripts',
				'resources' => 'app/resources',
			),
			'aliases' => array(
				'App'			=> 'Phpf\App\App',
				'Config'		=> 'Phpf\Config\Object',
				'Cache'			=> 'Phpf\Cache\Cache',
				'Events'		=> 'Phpf\Event\Container',
				'Request'		=> 'Phpf\Http\Request',
				'Response'		=> 'Phpf\Http\Response',
				'Router'		=> 'Phpf\Routes\Router',
				'Session'		=> 'Phpf\Util\Session',
				'Registry' 		=> 'Phpf\Util\Registry',
				'Filesystem'	=> 'Phpf\Filesystem\Filesystem',
				'Database'		=> 'Phpf\Database\Database',
			),
		);
	}
	
	/**
	 * Create a new application instance.
	 * 
	 * @param array|ArrayAccess $config Configuration array/object.
	 * @throws RuntimeException If $config is not array or ArrayAccess, or if 
	 * 							App instance with given ID already exists.
	 * @return \App
	 */
	public static function factory($config) {
		
		if (! is_array($config) && ! $config instanceof ArrayAccess) {
			throw new RuntimeException("Config must be array or instance of ArrayAccess.");
		}
		
		if (! isset($config['id'])) {
			$config['id'] = self::DEFAULT_ID;
		}
		
		if (isset(static::$instances[$config['id']])) {
			throw new RuntimeException('Application already exists.');
		}
		
		// create new instance
		return (static::$instances[$config['id']] = new static($config));
	}
	
	/**
	 * Construct the application
	 * 
	 * @param array|ArrayAccess Config array/object
	 */
	protected function __construct($config) {
		
		// create class aliaser, add aliases and register
		$this->set('aliaser', $aliaser = new ClassAliaser);
		
		$aliaser->addAliases($config['aliases'])->register();
				
		// set Config object
		$this->set('config', new \Config($config));
	}
	
	/**
	 * Returns the application namespace.
	 * 
	 * @return string Application namespace
	 */
	public function getNamespace() {
		return $this->get('config')->get('namespace');
	}
	
	/**
	 * Returns path to given arg or base application path.
	 * 
	 * @param null|string $to Name of path to get. Default null
	 * @return string Path to given arg or base path.
	 */
	public function getPath($to = null) {
		$paths = $this->get('config')->get('paths');
		if (isset($to)) {
			return isset($paths[$to]) ? $paths[$to] : null;
		}
		return $paths['app'];
	}
	
	/**
	 * Adds a (lazy) class alias.
	 * 
	 * @param string $from Fully resolved class to alias.
	 * @param string $to The class alias.
	 * @return $this
	 */
	public function alias($from, $to) {
		$this->aliaser->alias($from, $to);
		return $this;
	}
	
	/**
	 * Returns a component.
	 * 
	 * @param string $name Component name.
	 * @return object Component object if exists, otherwise null.
	 */
	public function get($name) {
		
		if (! isset($this->components[$name]))
			return null;
		
		if (is_object($this->components[$name]))
			return $this->components[$name];
		
		$class = $this->components[$name];
		
		return $class::instance();
	}
	
	/**
	 * Adds a component.
	 * 
	 * If $object is a string, the component will be added and 
	 * called as a singleton.
	 * 
	 * @param string $name Case-sensitive component name.
	 * @param object|string $object Component object or classname.
	 * @return $this
	 */
	public function set($name, $object) {
		
		if (is_string($object)) {
			return $this->setSingleton($name, $object);
		} 
		
		if (! is_object($object)) {
			$type = gettype($object);
			trigger_error("Non-singleton components must be objects - $type given for $name.");
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
	 * @param string $class Component class implementing singleton.
	 * @return $this
	 */
	public function setSingleton($name, $class) {
		
		if (! method_exists($class, 'instance')) {
			$class = is_object($class) ? get_class($class) : $class;
			trigger_error("Singletons must have 'instance()' method - Class $class does not.");
			return null;
		}
		
		if (is_object($class)) {
			$class = get_class($class);
		}
		
		$this->components[$name] = $class;
		
		return $this;
	}
	
	/**
	 * Sets cache driver based on config values, or lack thereof.
	 * 
	 * @param string $cache Classname to use for Cache (singleton).
	 * @return \Cache
	 */
	public function startCache($cache) {
		
		$this->setSingleton('cache', $cache); // set the class
		$cache = $this->get('cache'); // get the object
		$conf = $this->get('config')->get('cache');
		
		// get driver class
		if (isset($conf['driver-class'])) {
			$class = $conf['driver-class'];
		} elseif (isset($conf['driver'])) {
			$class = 'Phpf\\Cache\\Driver\\'.ucfirst($conf['driver']).'Driver';
		}
		
		if (isset($class) && class_exists($class, true)) {
			$cache->setDriver(new $class);
		} else {
			// set fallback driver
			$cache->setDriver(new \Phpf\Cache\Driver\StaticDriver);
		}
		
		return $cache;
	}
	
	/**
	 * Countable
	 */
	public function count(){
		return count($this->components);
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
		trigger_error('Cannot unset application components.', E_USER_NOTICE);
	}
	
	/**
	 * ArrayAccess
	 */
	public function offsetExists( $index ){
		return isset($this->components[$var]);
	}
	
	/**
	 * Magic __get()
	 */
	public function __get($var) {
		return $this->get($var);
	}
	
	/**
	 * Magic __set()
	 */
	public function __set($var, $val) {
		$this->set($var, $val);
	}
	
	/**
	 * Magic __isset()
	 */
	public function __isset($var) {
		return isset($this->components[$var]);
	}
	
	/**
	 * Magic __unset()
	 */
	public function __unset($var) {
		$this->offsetUnset($var);
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
