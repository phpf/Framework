<?php

namespace Phpf\Package;

use Phpf\Util\iManager;
use Phpf\Filesystem\Filesystem;
use Phpf\Event\Container as Events;

class Manager implements iManager {
	
	protected $packages = array();
	
	protected $loader;
	
	protected $events;
	
	/**
	 * Constructor
	 */
	public function __construct( Filesystem $filesystem, Events &$events ){
		$this->loader = new Loader($filesystem, $events);
		$this->events =& $events;
	}
	
	/**
	 * Implement iManager
	 * Manages 'packages'
	 */
	final public function manages(){
		return 'packages';
	}
	
	/**
	 * Adds a package object.
	 */
	public function add( iPackage $package ){
		$this->packages[ $package->getType() ][ $package->getId() ] = $package;
		return $this;
	}
	
	/**
	 * Returns boolean, whether a package exists given its UID, or type and ID.
	 */
	public function exists( $uid /* | $type, $id */ ){
		
		$typeid = $this->parseUid(func_get_args());
		list($type, $id) = $typeid;
		
		return isset($this->packages[ $type ][ $id ]);
	}
	
	/**
	 * Unsets a package given its UID, or type and ID.
	 * Note: This will not "disable" the package if it has been loaded.
	 */
	public function remove( $uid /* | $type, $id */ ){
		
		$typeid = $this->parseUid(func_get_args());
		list($type, $id) = $typeid;
		
		unset($this->packages[$type][$id]);
	}
	
	/**
	 * Returns a package given its UID, or type and ID.
	 */
	public function get( $uid /* | $type, $id */ ){
		
		$typeid = $this->parseUid(func_get_args());
		list($type, $id) = $typeid;
		
		return isset($this->packages[$type][$id]) ? $this->packages[$type][$id] : null;
	}
	
	/**
	 * Returns all package objects of given type.
	 */
	public function getAllOfType( $type ){
		return empty($this->packages[$type]) ? array() : $this->packages[$type];
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
	 * Returns indexed array of IDs for given package type.
	 */
	public function getIdsOfType( $type ){
			
		$pkgs = $this->getAllOfType($type);
		$ids = array();
		
		if ( ! empty($pkgs) ) {
			foreach( $pkgs as $pkg ) {
				$ids[] = $pkg->getId();
			}
		}
		
		return $ids;
	}
	
	/**
	 * Returns indexed array of package type strings.
	 */
	public function getTypes(){
		return array_keys($this->packages);
	}
	
	/**
	 * Creates a new array with 'type' and 'id' keys.
	 * 
	 * @param array $args	Arguments.	If only 1 element is present, it(the string) is parsed 
	 * 									as a dot-separated type/ID pair. Otherwise, the first
	 * 									two items are used as the type and ID, respectively.
	 */
	protected function parseUid( array $args ){
		
		if ( 1 < count($args) ){
			$type = $args[0];
			$id = $args[1];
		} else {
			$parts = explode('.', $args[0]);
			$type = $parts[0];
			$id = $parts[1];
		}
		
		return array($type, $id);
	}
	
}
