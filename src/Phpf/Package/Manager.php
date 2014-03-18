<?php

namespace Phpf\Package;

use Phpf\Util\iManager;
use Phpf\Filesystem\Filesystem;
use Phpf\Event\Container as Events;

class Manager implements iManager {
	
	const DEFAULT_LIBRARY_CLASS = 'Phpf\\Package\\Library';
	
	const DEFAULT_MODULE_CLASS = 'Phpf\\Package\\Module';
	
	protected $packages = array();
	
	protected $loader;
	
	protected $events;
	
	protected $config;
	
	protected $paths = array();
	
	protected $classes = array();
	
	/**
	 * Constructor
	 */
	public function __construct( Filesystem $filesystem, Events &$events, array $config ){
			
		$this->loader = new Loader($filesystem);
		$this->events =& $events;
		
		$this->config = $config;
		
		if (isset($config['paths'])) {
			$this->paths = $config['paths'];
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
	
	public function setLibraryClass( $class ) {
		$this->classes['library'] = $class;
		return $this;
	}
	
	public function setModuleClass( $class ) {
		$this->classes['module'] = $class;
		return $this;
	}
	
	public function getLibraryClass(){
		if (isset($this->classes['library'])) {
			return $this->classes['library'];
		}
		return self::DEFAULT_LIBRARY_CLASS;
	}
	
	public function getModuleClass(){
		if (isset($this->classes['module'])) {
			return $this->classes['module'];
		}
		return self::DEFAULT_MODULE_CLASS;
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
	public function addModule( $mod ) {
		$modClass = $this->getModuleClass();
		$this->add(new $modClass($mod, MODULES.ucfirst($mod)));
	}
	
	/**
	 * Adds a library by name.
	 */
	public function addLibrary( $lib ) {
		$libClass = $this->getLibraryClass();
		$this->add(new $libClass($lib, LIBRARY.ucfirst($lib)));
	}
	
	/**
	 * Adds an array of packages by UID. Optionally loads them.
	 */
	public function addPackages( array $packages, $load = false ) {
			
		foreach($packages as $package) {
			
			if (0 === strpos($package, 'library.')) {
			
				$lib = substr($package, 8);
			
				$this->addLibrary($lib);
				
				if ($load) {
					$this->load('library.'.$lib);
				}
			
			} elseif (0 === strpos($package, 'module.')) {
					
				$mod = substr($package, 7);
			
				$this->addModule($mod);
				
				if ($load) {
					$this->load('module.'.$mod);
				}
			}
		}
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
		
		if ($package->isLoaded()){
			throw new Exception\Loaded(ucfirst($package->getType())." '$package->getId()' is already loaded.");
		}
		
		$this->loader->load($package);
		
		$this->events->trigger($package->getUid().'.load', $package);
			
		return $this;
	}
	
	/**
	 * Returns boolean, whether a package is loaded given its UID.
	 */
	public function isLoaded( $uid ){
		
		$pkg = $this->get($uid);
		
		if (empty($pkg) || ! $pkg instanceof iPackage){
			return false;
		}
		
		return $pkg->isLoaded();
	}
	
	/**
	 * Returns all package objects of given type.
	 */
	public function getAllOfType( $type ){
		return empty($this->packages[$type]) ? array() : $this->packages[$type];
	}
	
	/**
	 * Loads all packages of given type.
	 * If package is already loaded, no error is reported.
	 */
	public function loadAllOfType( $type ){
		
		$all = $this->getAllOfType($type);
		
		if ( ! empty($all) ){
			foreach($all as $pkg){
				$this->load($pkg);
			}
		}
		
		return $this;
	}
	
	/**
	 * Returns indexed array of all package UIDs.
	 */
	public function getUids(){
		
		$uids = array();
		
		if ( ! empty($this->packages) ) {
			foreach( $this->packages as $pkg ) {
				$uids[] = $pkg->getUid();
			}
		}
		
		return $uids;
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
					$value = substr($str, $pos+strlen($pos));
					return $op;
				}
			}
			return null;
		};
		
		foreach($packages as $condition => $_packages) {
			
			$val = '';
			$oper = $findDelim($condition, $val);
			
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
	 * Creates a new array with 'type' and 'id' keys.
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
