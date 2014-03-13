<?php

namespace Phpf\Package;

/**
 * Modules are customizable packages.
 * 
 * Each module should define a class that extends Phpf\Package\Module.
 * Custom contructor and load methods are permitted.
 */
class Module extends AbstractPackage {

	final public function getType(){
		return 'module';
	}
	
}