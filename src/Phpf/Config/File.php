<?php
/**
 * @package Phpf
 * @subpackage Config
 */

namespace Phpf\Config;

/**
 * Class for generating config object from a file. 
 *
 * @package Config
 * @subpackage File
 */
abstract class File extends Object {
	
	protected $configFile;
	
	abstract public function parse();
	
	public function setConfigFile( $file ){
		$this->configFile = $file;
		return $this;
	}
}
