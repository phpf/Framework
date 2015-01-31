<?php

namespace xpl\Framework;

use xpl\Common\Storage\Container;
use xpl\System\Server;

class Env extends Container 
{
	
	/**
	 * Environment type (e.g. "development", "production")
	 * @var string
	 */	
	protected $type;
	
	/**
	 * Named directory paths.
	 * @var array
	 */
	protected $paths;
	
	public function __construct($type, $root_path) {
			
		if (! $realpath = realpath($root_path)) {
			throw new \InvalidArgumentException("Invalid root path given: '$root_path'.");
		}
		
		$this->type = strtolower($type);
		$this->paths = array(
			'root' => $realpath.DIRECTORY_SEPARATOR
		);
	}
	
	public function configure($config) {
		
		$config = (array)$config;
		
		if (isset($config['paths'])) {
			$this->setPaths($config['paths']);
			unset($config['paths']);
		}
		
		$this->import($config);
		
		return $this;
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function is($type) {
		return strtolower($type) === $this->type;
	}
	
	public function setPath($name, $path) {
		$this->paths[$name] = rtrim($path, '/\\').DIRECTORY_SEPARATOR;
		return $this;
	}
	
	public function setPaths(array $paths) {
		foreach($paths as $name => $path) {
			$this->setPath($name, $path);
		}
		return $this;
	}
	
	public function getPath($name = null) {
		if (! isset($name)) {
			return $this->paths['root'];
		}
		return isset($this->paths[$name]) ? $this->paths[$name] : null;
	}
	
	public function getDomain() {
		
		if ($this->has('domain')) {
			return $this->get('domain');
		}
		
		return Server::getDomainName(Server::DOMAIN|Server::TLD);
	}
	
	public function getSubdomain() {
		
		if ($this->has('subdomain')) {
			return $this->get('subdomain');
		}
		
		return Server::getDomainName(Server::SUBDOMAIN);
	}
	
}
