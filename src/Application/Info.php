<?php

namespace xpl\Framework\Application;

class Info extends \xpl\Common\Object implements \JsonSerializable 
{
	
	protected $path;
	protected $name;
	protected $label;
	protected $host;
	
	public function __construct($path) {
		$this->path = realpath($path).DIRECTORY_SEPARATOR;
		$this->name = basename($this->path);
		$this->label = trim(ucwords(str_replace(array('_','-','.'), ' ', $this->name)));
		$this->host = ('main' == $this->name) ? env('domain') : $this->name.'.'.env('domain');
	}
	
	public function getId() {
		return $this->name;
	}
	
	public function getPath() {
		return $this->path;
	}
	
	public function getLabel() {
		return $this->label;
	}
	
	public function getHost() {
		return $this->host;
	}
	
	public function getUrl($path = '/', $query = null) {
		return http_build_url($path, array('host' => $this->host, 'query' => $query));
	}
	
	public function jsonSerialize() {
		return $this->toArray();
	}
	
}
