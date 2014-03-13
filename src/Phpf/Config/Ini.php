<?php
/**
 * @package Phpf
 * @subpackage Config
 */

namespace Phpf\Config;

/**
 * Class for generating config object from .ini file.
 * 
 * @package Config
 * @subpackage Ini
 */
class Ini extends File {
	
	protected $showSections = false;
		
	public function setShowSections( $val ){
		$this->showSections = (bool) $val;	
	}
	
	public function parse(){
			
		$config = array();
		$data = parse_ini_file( $file, $this->showSections );
		
		foreach ($data as $k => $v) {
			
			$ks = array_reverse( explode('.', $k) );
			
			foreach ($ks as $kss) {
				$v = array($kss => $v);
			}
			
			$config[] = $v;
		}
	
		$this->data = (0 === count($config)) ? array() : call_user_func_array('array_replace_recursive', $config);
	}
}