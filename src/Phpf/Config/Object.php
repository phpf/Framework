<?php
/**
 * @package Phpf
 * @subpackage Config
 */

namespace Phpf\Config;

use Phpf\Util\DataContainer;

/**
 * A basic Config object.
 */ 
class Object extends DataContainer {
	
	protected $data;
	
	protected $read_only = false;
	
	protected $has_defaults = true;
	
	protected $defaults = array();
	
	public function __construct( array $data = array() ){
		$this->data = $data;
	}
	
	/**
	 * Sets a config item value.
	 */
	public function __set( $var, $val ){
		
		if ( $this->isReadOnly() ){
			trigger_error("Cannot set $var - config object is read-only.");
			return null;
		}
		
		$this->data[ $var ] = $val;
		
		return $this;
	}
	
	/**
	 * Returns a config item value.
	 */
	public function __get( $var ){
			
		if (isset($this->data[$var])){
			return $this->data[$var];
		}
		
		return $this->hasDefaults() ? $this->getDefault($var) : null;
	}
	
	/**
	 * Whether a config item exists.
	 */
	public function __isset( $var ){
		
		if (isset($this->data[$var])) {
			return true;
		}
		
		return $this->hasDefaults() ? (bool) $this->getDefault($var) : false;
	}
	
	/**
	 * Removes a config item.
	 */
	public function __unset( $var ){
		
		if ( $this->isReadOnly() ){
			trigger_error("Cannot unset $var - config object is read-only.");
			return null;
		}
		
		unset($this->data[$var]);
	}

	/**
	 * Sets a config item value and returns it.
	 * 
	 * Useful for situations where you want to simulataneously set a config
	 * property and another variable/object property/etc.
	 */
	public function setr( $var, $val ){
		$this->set($var, $val);
		return $val;
	}
	
	/**
	 * Set whether the config items are read-only.
	 */
	public function setReadOnly( $val ){
		$this->read_only = (bool) $val;	
		return $this;
	}
	
	/**
	 * Set whether the config items can have defaults.
	 */
	public function setHasDefaults( $val ){
		$this->has_defaults = (bool) $val;
		return $this;
	}
	
	/**
	 * Whether the config items are read-only.
	 */
	public function isReadOnly(){
		return $this->read_only;	
	}
	
	/**
	 * Whether the config items can have defaults.
	 */
	public function hasDefaults(){
		return $this->has_defaults;	
	}
	
	/**
	 * Set an item's default value.
	 */
	public function setDefault( $var, $val ){
			
		if ( ! $this->hasDefaults() ){
			trigger_error("Config object may not have defaults. To use defaults, call setHasDefaults(true)");
			return null;
		}
		
		$this->defaults[ $var ] = $val;
		
		return $this;
	}
	
	/**
	 * Get an item's default value.
	 */
	public function getDefault( $var ){
		
		if ( ! $this->hasDefaults() ){
			trigger_error("Config object does not have defaults.");
			return null;
		}
		
		return isset($this->defaults[$var]) ? $this->defaults[$var] : null;
	}
}
