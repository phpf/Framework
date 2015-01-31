<?php

namespace xpl\Framework\Application;

class Config extends \xpl\Common\Storage\Config 
{
	
	public function __construct($name, $path) {
		$this->set('name', $name);
		$this->set('rootpath', $path);
	}
	
	public function setPath($name, $path) {
		
		if (0 !== stripos($path, $this->get('rootpath'))) {
			$path = $this->get('rootpath').ltrim($path, '/\\');
		}
		
		if ($realpath = realpath($path)) {
			$this->set('paths.'.$name, $realpath.DIRECTORY_SEPARATOR);
		}
		
		return $this;
	}
	
	public function getPath($name = null) {
			
		if (null === $name) {
			return $this->get('rootpath');
		}
		
		return $this->get('paths.'.$name);
	}
	
}
