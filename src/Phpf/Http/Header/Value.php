<?php

namespace Phpf\Http\Header;

/**
 * Represents a single header value.
 */
class Value {
	
	public $value;
	
	protected $header;
	
	public function __construct(HeaderInterface $header) {
		$this->header = $header;
	}
	
	public function getHeader() {
		return $this->header;
	}
	
	public function setValue($value) {
		$this->value = $value;
		return $this;
	}
	
	public function getValue() {
		return $this->value;
	}
	
	public function __toString() {
		return (string) $this->value;
	}
}
