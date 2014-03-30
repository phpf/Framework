<?php

namespace Phpf\Routes\Simple;

use SimpleXMLElement;

class Manager {
	
	const DEFAULT_XML_CLASS = 'XmlElement';
	
	protected $xml;
	
	protected $parameters = array();
	
	protected $endpoints = array();
	
	public static function loadXml($str_or_file, $class_name = self::DEFAULT_XML_CLASS, $options = 0, $ns = null, $is_prefix = false) {
		if (is_file($str_or_file) && is_readable($str_or_file)) {
			return simplexml_load_file($str_or_file, $class_name);
		} else {
			return simplexml_load_string($str_or_file, $class_name);
		}
	}
		
	public static function newFromXml(SimpleXMLElement $xml) {
		
		$_this = new static();
		$_this->xml = $xml;
		
		foreach($_this->xml->parameter as $param) {
			$_this->parameters[$param->getAttribute('name')] = Parameter::newFromXml($param);
		}
		
		foreach($_this->xml->endpoint as $ep) {
			$_this->endpoints[$ep->getAttribute('name')] = Endpoint::newFromXml($ep);
		}
		
		return $_this;
	}
	
	public function getParameter($name) {
		return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
	}
	
	public function getEndpoint($name) {
		return isset($this->endpoints[$name]) ? $this->endpoints[$name] : null;
	}
	
}
