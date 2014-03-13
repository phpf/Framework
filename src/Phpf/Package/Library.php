<?php

namespace Phpf\Package;

/**
 * Libraries are simple packages, basically a concretion of AbstractPackage.
 * 
 * Libraries cannot override the object constructor or the load() 
 * method defined by AbstractPackage.
 */
class Library extends AbstractPackage {
	
	final public function __construct( $id, $path ){
		parent::__construct($id, $path);
	}
	
	final public function load(){
		return parent::load();
	}
	
	final public function isLoaded(){
		return parent::isLoaded();
	}
	
	final public function getId(){
		return $this->id;
	}
	
	final public function getPath(){
		return $this->path;
	}
	
	final public function getType(){
		return 'library';
	}
	
}
