<?php

namespace Phpf\Http\Header;

/**
 * Represents a single HTTP header.
 */
class Header implements HeaderInterface {
	
	public function __construct($name, $value = null) {
		$this->setName($name);
		if (isset($value))
			$this->setValue($value);
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getValue() {
		return $this->value;
	}
	
	public function __toString() {
		return sprintf("%s: %s", $this->name, $this->value);
	}
	
	protected function setValue($value) {
		$this->value = $value;
		return $this;
	}
	
	protected function setName($name) {
		$this->name = str_replace(array('http-','_'), array('', '-'), strtolower($name));
		return $this;
	}
	
}
