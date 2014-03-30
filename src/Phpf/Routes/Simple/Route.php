<?php

namespace Phpf\Routes\Simple;

use SimpleXMLElement;

class Route {
		
	protected $endpoint;
	
	protected $path;
	
	protected $action;
	
	protected $methods;
	
	public function __construct(array $data) {
		
		foreach($data as $k => $v) {
			$this->$k = $v;
		}
	}
	
	public static function newFromXml(SimpleXMLElement $xml) {
		
		$data = array();
		
		foreach(array('path', 'action', 'methods') as $attr) {
			if ($val = $xml->getAttribute($attr)) {
				$data[$attr] = $val;
			} elseif (isset($endpoint)) {
				$data[$attr] = $endpoint->getDefault($attr);
			}
		}
		
		return new static($data);
	}
	
	public function setEndpoint(Endpoint &$endpoint) {
		$this->endpoint =& $endpoint;
		return $this;
	}
	
	public function getPath(){
		return $this->path;
	}
	
	public function getAction() {
		return $this->action;
	}
	
	public function getMethods() {
		return $this->methods;
	}
	
	public function isMethodAllowed($method) {
		return false !== strpos($this->methods, $method);
	}
	
}
