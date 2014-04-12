<?php

namespace Phpf\Http\Header;

interface HeaderInterface {
	
	public function getName();
	
	public function getValue();
	
	public function __toString();

}
	