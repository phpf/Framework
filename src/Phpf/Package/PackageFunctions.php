<?php

namespace Phpf\Package;

class PackageFunctions {
	
	protected $vendor;
	
	protected $functions = array();
	
	public function __construct( $namespace ){
		$this->vendor = trim($namespace, '\\');
	}
	
	public function getNamespace(){
		return $this->vendor;
	}
	
	public function load( $package ){
		
		$namespace = $this->ns($package);
		
		if (isset($this->functions[$namespace])) {
			return $this->functions[$namespace];
		}
		
		if (class_exists("$namespace\\Functional", true)) {
			return $this->functions[$namespace] = true;
		} else {
			return $this->functions[$namespace] = false;
		}
	}
	
	public function loaded( $package ){
		
		$namespace = $this->ns($package);
		
		if (isset($this->functions[$namespace])) {
			return $this->functions[$namespace];
		}
		
		return false;
	}
	
	protected function ns( $package ){
		return $this->vendor . '\\' . ucfirst(ltrim($package, '\\'));
	}
		
}
