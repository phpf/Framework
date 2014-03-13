<?php
/**
 * @package Phpf
 * @subpackage Config
 */

namespace Phpf\Config;

use ArrayAccess;
use Countable;
use Phpf\Util\Arr;

/**
 * A basic Config object.
 */ 
class Object implements ArrayAccess, Countable {
	
	protected $data;
	
	protected $read_only = false;
	
	protected $has_defaults = false;
	
	protected $defaults = array();
	
	public function __construct( array $data = array() ){
		$this->data = $data;
	}
	
	public function offsetGet($index){
		return $this->get($index);
	}
	
	public function offsetSet($index, $newval){
		$this->set($index, $newval);
	}
	
	public function offsetExists($index){
		return $this->exists($index);
	}
	
	public function offsetUnset($index){
		$this->remove($index);
	}
	
	public function count(){
		return count($this->data);
	}
	
	/**
	 * Sets a config item value.
	 */
	public function set( $var, $val ){
		
		if ( $this->isReadOnly() ){
			throw new \RuntimeException("Cannot set $var - config object is read-only.");
		}
		
		if ( false === strpos($var, '.') ){
			$this->data[ $var ] = $val;
		} else {
			Arr::dotSet($this->data, $var, $val);	
		}
		
		return $this;
	}
	
	/**
	 * Returns a config item value.
	 */
	public function get( $var ){
		
		if ( false === strpos($var, '.') ){
			return isset($this->data[ $var ]) ? $this->data[ $var ] : $this->getDefault($var);
		}
		
		return Arr::dotGet($this->data, $var);
	}
	
	/**
	 * Whether a config item exists.
	 */
	public function exists( $var ){
		$val = $this->get($var);
		return !empty($val);
	}
	
	/**
	 * Removes a config item.
	 */
	public function remove( $var ){
		Arr::dotUnset($this->data, $var);
		return $this;
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
			trigger_error("Config object may not have defaults. To use defaults, call ->setHasDefaults(true)");
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
		
		if ( ! isset($this->defaults[ $var ]) )
			return null;
		
		return $this->defaults[ $var ];
	}
}
