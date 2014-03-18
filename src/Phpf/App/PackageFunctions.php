<?php

namespace Phpf\App;

class PackageFunctions {
	
	protected $namespace;
	
	protected $functions = array();
	
	public function __construct( $namespace ){
		$this->namespace = trim($namespace, '\\');
	}
	
	public function getNamespace(){
		return $this->namespace;
	}
	
	public function load( $package ){
		
		$pkg = $this->namespace . '\\' . ucfirst(ltrim($package, '\\'));
		
		if ( array_key_exists($pkg, $this->functions) ){
			return $this->functions[$pkg];
		}
		
		if ( class_exists("$pkg\\Functional") ){
			return $this->functions[$pkg] = true;
		} else {
			return $this->functions[$pkg] = false;
		}
	}
	
	public function loaded( $package ){
		
		$pkg = $this->namespace . '\\' . ucfirst(ltrim($package, '\\'));
		
		if ( array_key_exists($pkg, $this->functions) ){
			return $this->functions[$pkg];
		}
		
		return false;
	}
		
}
