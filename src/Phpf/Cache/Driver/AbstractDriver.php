<?php

namespace Phpf\Cache\Driver;

use Phpf\Cache\Cache;

abstract class AbstractDriver {
	
	protected $engine;
	
	protected $prefix;
	
	protected $serializer;
	
	protected $unserializer;
	
	final public function __construct(){
		$this->engine = $this->getEngine();
		$this->prefix = defined('CACHE_PREFIX') ? CACHE_PREFIX : md5($_SERVER['HTTP_HOST']) . '|';
		$this->serializer = function_exists('igbinary_serialize') ? 'igbinary_serialize' : 'serialize';
		$this->unserializer = function_exists('igbinary_unserialize') ? 'igbinary_unserialize' : 'unserialize';
	}
	
	abstract public function getEngine();
	
	abstract public function getPrefix( $group = Cache::DEFAULT_GROUP );
	
	abstract public function exists( $id, $group = Cache::DEFAULT_GROUP );
	
	abstract public function get( $id, $group = Cache::DEFAULT_GROUP );
		
	abstract public function set( $id, $value, $group = Cache::DEFAULT_GROUP, $ttl = Cache::DEFAULT_TTL );
		
	abstract public function delete( $id, $group = Cache::DEFAULT_GROUP );
	
	abstract public function incr( $id, $val = 1, $group = Cache::DEFAULT_GROUP, $ttl = Cache::DEFAULT_TTL );
	
	abstract public function decr( $id, $val = 1, $group = Cache::DEFAULT_GROUP, $ttl = Cache::DEFAULT_TTL );
	
	abstract public function flush();
	
	abstract public function flushGroup( $group );
	
}