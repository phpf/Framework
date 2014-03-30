<?php

namespace Phpf\Package;

use Phpf\Util\iManager;
use Phpf\App\App;

class Manager implements iManager {
	
	const DEFAULT_LIBRARY_CLASS = 'Phpf\\Package\\Library';
	
	const DEFAULT_MODULE_CLASS = 'Phpf\\Package\\Module';
	
	protected $packages = array();
	
	protected $loader;
	
	protected $config;
	
	protected $functional = array();
	
	protected $paths = array();
	
	protected $classes = array();
	
	/**
	 * Constructor
	 */
	public function __construct( App $app ){
		
		$this->app = $app;
		
		$this->loader = new Loader($app);
		
		$this->config = $app->get('config')->get('packages');
	}
	
	public function init() {
		
		if (! empty($this->config['functions'])) {
			
			foreach($this->config['functions'] as $namespace => $packages) {
					
				$this->initFunctions($namespace);
				
				array_walk($packages, function ($package) use($namespace) {
					$this->loadFunctions($namespace, $package);
				});
			}
		}
		
		if (! empty($this->config['preload'])) {
			$this->addPackages($this->config['preload'], true);
		}
		
		if (! empty($this->config['ondemand'])) {
			$this->addPackages($this->config['ondemand'], false);
		}
		
		if (! empty($this->config['conditional'])) {
			$this->parseConditionalPackages($this->config['conditional']);
		}
	}
	
	public function setClass( $package_type, $class ) {
		$this->classes[$package_type] = $class;
		return $this;
	}
	
	public function getClass($package_type){
			
		if (isset($this->classes[$package_type])) {
			return $this->classes[$package_type];
		}
		
		$const = 'DEFAULT_'.strtoupper($package_type).'_CLASS';
		
		if (defined("static::$const")) {
			return constant("static::$const");
		}
		
		trigger_error("No class set for package type $package_type.");
	}
	
	public function setPath($package_type, $path) {
		$this->paths[$package_type] = rtrim($path, '/\\') . '/';
		return $this;
	}
	
	public function getPath($package_type) {
			
		if (isset($this->paths[$package_type]))
			return $this->paths[$package_type];
		
		$paths = $this->app->get('config')->get('paths');
		
		if ($path = $paths[$package_type]) {
			return $path;
		}
		
		trigger_error("No path set for package type $package_type.");
	}
	
	/**
	 * Implement iManager
	 * Manages 'packages'
	 */
	final public function manages(){
		return 'packages';
	}
	
	/**
	 * Returns a package given its UID, or type and ID.
	 */
	public function get( $uid /* | $type, $id */ ){
		
		list($type, $id) = $this->parseUid(func_get_args());
		
		return isset($this->packages[$type][$id]) ? $this->packages[$type][$id] : null;
	}
	
	/**
	 * Returns boolean, whether a package exists given its UID, or type and ID.
	 */
	public function exists( $uid /* | $type, $id */ ){
		
		list($type, $id) = $this->parseUid(func_get_args());
		
		return isset($this->packages[ $type ][ $id ]);
	}
	
	/**
	 * Unsets a package given its UID, or type and ID.
	 * Note: This will not "disable" the package if it has been loaded.
	 */
	public function remove( $uid /* | $type, $id */ ){
		
		list($type, $id) = $this->parseUid(func_get_args());
		
		unset($this->packages[$type][$id]);
	}
	
	/**
	 * Adds a package object.
	 */
	public function add( iPackage $package ){
		$this->packages[ $package->getType() ][ $package->getId() ] = $package;
		return $this;
	}
	
	/**
	 * Adds a module by name.
	 */
	public function addModuleByName( $mod ) {
		$modClass = $this->getClass('module');
		$dirpath = $this->getPath('module');
		$this->add(new $modClass($mod, rtrim($dirpath, '/\\').'/'.ucfirst($mod)));
	}
	
	/**
	 * Adds a library by name.
	 */
	public function addLibraryByName( $lib ) {
		$libClass = $this->getClass('library');
		$dirpath = $this->getPath('library');
		$this->add(new $libClass($lib, rtrim($dirpath, '/\\').'/'.ucfirst($lib)));
	}
	
	/**
	 * Adds an array of packages by UID. Optionally loads them.
	 */
	public function addPackages( array $packages, $load = false ) {
			
		array_walk($packages, function ($package) use ($load) {
			if (0 === strpos($package, 'library.')) {
				$lib = substr($package, 8);
				$this->addLibraryByName($lib);
				if ($load) {
					$this->load('library.'.$lib);
				}
			} elseif (0 === strpos($package, 'module.')) {
				$mod = substr($package, 7);
				$this->addModuleByName($mod);
				if ($load) {
					$this->load('module.'.$mod);
				}
			}
		});
	}
	
	/**
	 * Loads a package given its UID, or type and ID.
	 */
	public function load( $uid /* | $type, $id */ ){
		
		$args = func_get_args();
		
		if ($args[0] instanceof iPackage) {
			$package =& $args[0];
		} elseif (isset($args[1])) {
			$package = $this->get($args[0], $args[1]);
		} else {
			$package = $this->get($args[0]);
		}
		
		if (empty($package)) {
			throw new Exception\Unknown("Empty package given.");
		} 
		
		if (! $package instanceof iPackage) {
			throw new Exception\Invalid("Invalid package - packages must implement Phpf\Package\iPackage.");
		}
		
		if ($package->isLoaded()) {
			throw new Exception\Loaded(ucfirst($package->getType())." '$package->getId()' is already loaded.");
		}
		
		$this->loader->load($package);
		
		$this->app->get('events')->trigger($package->getUid().'.load', $package);
			
		return $this;
	}
	
	/**
	 * Returns boolean, whether a package is loaded given its UID.
	 */
	public function isLoaded( $uid ){
		
		$pkg = $this->get($uid);
		
		if (empty($pkg) || ! $pkg instanceof iPackage) {
			return false;
		}
		
		return $pkg->isLoaded();
	}
	
	/**
	 * Sets up functions control for namespace
	 */
	public function initFunctions($namespace){
		$this->functional[$namespace] = new Functions($namespace);
		return $this;
	}
	
	/**
	 * Loads functions for a package.
	 *  
	 * @param string Package name
	 * @return boolean Whether functions were loaded.
	 */
	public function loadFunctions( $namespace, $package ){
		
		if (! isset($this->functional[$namespace])) {
			throw new \RuntimeException("Functions object for namespace $namespace is not set.");
		}
		
		return $this->functional[$namespace]->load($package);
	}
	
	
	/**
	 * Returns true if functions are loaded for a package,
	 * otherwise returns false.
	 * 
	 * @param string Package name
	 * @return boolean True if functions loaded, otherwise false.
	 */
	public function functionsLoaded( $namespace, $package ){
		
		if (! isset($this->functional[$namespace])) {
			throw new \RuntimeException("Functions object for namespace $namespace is not set.");
		}
		
		return $this->functional[$namespace]->loaded($package);
	}
	
	/**
	 * Loads all packages of given type.
	 */
	public function loadAllOfType( $type ){
		
		$all = $this->getAllOfType($type);
		
		if (! empty($all)){
			array_walk($all, function (&$pkg) {
				$this->load($pkg);
			});
		}
		
		return $this;
	}
	
	/**
	 * Returns all package objects of given type.
	 */
	public function getAllOfType( $type ){
		return empty($this->packages[$type]) ? array() : $this->packages[$type];
	}
	
	/**
	 * Returns indexed array of package type strings.
	 */
	public function getTypes(){
		return array_keys($this->packages);
	}
	
	/**
	 * Parses array of conditionally loaded packages from config array.
	 */
	protected function parseConditionalPackages(array $packages) {
	
		$operators = array('<=', '>=', '<', '>', '!', '=');
		
		$findDelim = function ($str, &$value = null) use($operators) {
			
			foreach($operators as $op) {
				if (false !== $pos = strpos($str, $op)) {
					$value = substr($str, $pos+strlen($op));
					return $op;
				}
			}
			return null;
		};
		
		foreach($packages as $condition => $_packages) {
			
			$val = '';
			$oper = $findDelim($condition, $val);
			
			if (is_null($oper)) {
				continue;
			}
			
			switch(strtoupper(substr($condition, 0, 3))) {
				
				case 'PHP':
					
					if ('!' === $oper) {
						$oper = '!='; // format for version_compare()
					}
					
					if (version_compare(PHP_VERSION, $val, $oper) > 0) {
						// PHP version is outside given range
						$this->addPackages($_packages, true);
					}
					
					break;
				
				case 'EXT':
					
					if ('!' === $oper && ! extension_loaded($val)) {
						// Extension is not loaded
						$this->addPackages($_packages, true);
					}
					
					break;	
			}
		}
	}
	
	/**
	 * used with list($type, $id)
	 *  
	 * @param array $args	Arguments.	If only 1 element is present, it(the string) is parsed 
	 * 									as a dot-separated type/ID pair. Otherwise, the first
	 * 									two items will be used as the type and ID, respectively.
	 */
	protected function parseUid( array $args ){
			
		if ( 1 === count($args) ){
			return explode('.', $args[0]);
		}
		
		return $args;
	}
	
}
