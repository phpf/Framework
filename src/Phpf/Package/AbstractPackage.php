<?php

namespace Phpf\Package;

abstract class AbstractPackage implements iPackage {
		
	protected $id;
	
	protected $path;

	protected $loaded = false;
	
	/**
	 * Returns package type, a lowercase string.
	 */
	abstract public function getType();
	
	/**
	 * Returns dot-concatenated package type and ID.
	 */
	final public function getUid(){
		return $this->getType() . '.' . $this->getId();
	}
	
	/**
	 * Sets $id and $path.
	 * ID should be lowercase with no special chars.
	 * Path should not have trailing slash.
	 */
	public function __construct( $id, $path ){
		$this->id = strtolower($id);
		$this->path = rtrim($path, '/\\');
	}
	
	/**
	 * Returns package ID, a lowercase string.
	 */
	public function getId(){
		return $this->id;
	}
	
	/**
	 * Returns package directory path with no trailing slash.
	 */
	public function getPath(){
		return $this->path;
	}
	
	/**
	 * Returns true if package is loaded, otherwise false.
	 */
	public function isLoaded(){
		return $this->loaded;
	}
	
	/**
	 * Sets $loaded property.
	 */
	public function setLoaded( $val ){
		$this->loaded = (bool)$val;
		return $this;
	}
}
